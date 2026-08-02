<?php

namespace Tests\Feature;

use App\Models\InventoryLot;
use App\Models\Organization;
use App\Models\PackageLabel;
use App\Models\RetailLocation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RetailWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_retailer_receives_reserves_releases_and_sells_inventory_idempotently(): void
    {
        $this->seed();
        $retailer = User::where('email', 'retailer@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($retailer);
        $location = RetailLocation::where('organization_id', $retailer->primaryOrganization()?->id)->firstOrFail();
        $label = PackageLabel::whereDoesntHave('retailReceipt')->firstOrFail();

        $this->getJson('/api/v1/retail/incoming-labels')->assertOk()->assertJsonFragment(['label_code' => $label->label_code]);
        $this->postJson("/api/v1/retail/package-labels/{$label->id}/receive", ['retail_location_id' => $location->id, 'received_package_count' => 3])->assertUnprocessable();
        $lot = $this->postJson("/api/v1/retail/package-labels/{$label->id}/receive", ['retail_location_id' => $location->id, 'received_package_count' => 4, 'condition_temperature' => -1.1, 'expires_at' => now()->addDays(10)->toIso8601String()])->assertCreated()->assertJsonPath('data.status', 'IN_STOCK')->json('data');
        $this->postJson("/api/v1/retail/package-labels/{$label->id}/receive", ['retail_location_id' => $location->id, 'received_package_count' => 4])->assertConflict();

        $this->postJson("/api/v1/retail/inventory-lots/{$lot['id']}/reserve", ['quantity' => 2])->assertOk()->assertJsonPath('data.reserved_packages', 2);
        $this->postJson("/api/v1/retail/inventory-lots/{$lot['id']}/reserve", ['quantity' => 3])->assertUnprocessable();
        $this->postJson("/api/v1/retail/inventory-lots/{$lot['id']}/release", ['quantity' => 1])->assertOk()->assertJsonPath('data.available_packages', 3);

        $reference = (string) Str::uuid();
        $payload = ['retail_location_id' => $location->id, 'client_reference' => $reference, 'items' => [['inventory_lot_id' => $lot['id'], 'quantity' => 3, 'unit_price' => 1250.50]]];
        $sale = $this->postJson('/api/v1/retail/sales', $payload)->assertCreated()->assertJsonPath('data.total', '3751.50')->json('data');
        $this->postJson('/api/v1/retail/sales', $payload)->assertCreated()->assertJsonPath('data.id', $sale['id']);
        $changedReplay = $payload;
        $changedReplay['items'][0]['unit_price'] = 1251;
        $this->postJson('/api/v1/retail/sales', $changedReplay)->assertConflict();
        $this->assertDatabaseCount('retail_sales', 1);
        $this->assertDatabaseHas('inventory_lots', ['id' => $lot['id'], 'available_packages' => 0, 'reserved_packages' => 1, 'sold_packages' => 3, 'status' => 'RESERVED']);
        $this->postJson('/api/v1/retail/sales', ['retail_location_id' => $location->id, 'client_reference' => (string) Str::uuid(), 'items' => [['inventory_lot_id' => $lot['id'], 'quantity' => 1, 'unit_price' => 1200]]])->assertUnprocessable();
        $this->getJson("/api/v1/retail/sales/{$sale['id']}")->assertOk()->assertJsonPath('data.items.0.quantity', 3);
        $this->getJson('/api/v1/retail/dashboard')->assertOk()->assertJsonPath('data.sold_packages', 3);
        $this->assertDatabaseHas('traceability_events', ['fish_batch_id' => $lot['fish_batch_id'], 'event_type' => 'RETAIL_SALE']);
    }

    public function test_recalled_and_expired_lots_cannot_be_sold(): void
    {
        $this->seed();
        $retailer = User::where('email', 'retailer@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($retailer);
        $location = RetailLocation::where('organization_id', $retailer->primaryOrganization()?->id)->firstOrFail();
        $lot = InventoryLot::where('organization_id', $retailer->primaryOrganization()?->id)->firstOrFail();
        $this->postJson("/api/v1/retail/inventory-lots/{$lot->id}/recall", [])->assertUnprocessable();
        $this->postJson("/api/v1/retail/inventory-lots/{$lot->id}/recall", ['reason' => 'Supplier issued a precautionary recall.'])->assertOk()->assertJsonPath('data.status', 'RECALLED');
        $this->postJson('/api/v1/retail/sales', ['retail_location_id' => $location->id, 'client_reference' => (string) Str::uuid(), 'items' => [['inventory_lot_id' => $lot->id, 'quantity' => 1, 'unit_price' => 1000]]])->assertConflict();

        $label = PackageLabel::whereDoesntHave('retailReceipt')->firstOrFail();
        $second = $this->postJson("/api/v1/retail/package-labels/{$label->id}/receive", ['retail_location_id' => $location->id, 'received_package_count' => $label->package_count])->assertCreated()->json('data');
        $this->postJson("/api/v1/retail/inventory-lots/{$second['id']}/expire", ['reason' => 'The verified shelf-life window elapsed.'])->assertOk()->assertJsonPath('data.status', 'EXPIRED');
        $this->postJson('/api/v1/retail/sales', ['retail_location_id' => $location->id, 'client_reference' => (string) Str::uuid(), 'items' => [['inventory_lot_id' => $second['id'], 'quantity' => 1, 'unit_price' => 1000]]])->assertConflict();
    }

    public function test_retail_inventory_and_sales_are_isolated_by_organization(): void
    {
        $this->seed();
        $owner = User::where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $lot = InventoryLot::where('organization_id', $owner->primaryOrganization()?->id)->firstOrFail();
        $otherOrganization = Organization::create(['name' => 'Other Retailer', 'code' => 'RET-OTHER', 'type' => 'RETAILER']);
        $otherLocation = RetailLocation::create(['organization_id' => $otherOrganization->id, 'code' => 'STORE-1', 'name' => 'Other Store']);
        $other = User::create(['name' => 'Other Retailer', 'email' => 'other-retailer@example.test', 'password' => Hash::make('Password1234'), 'status' => 'ACTIVE']);
        $other->roles()->attach(Role::where('name', 'RETAILER')->firstOrFail());
        $other->organizations()->attach($otherOrganization, ['is_primary' => true]);
        Sanctum::actingAs($other);

        $this->getJson("/api/v1/retail/inventory-lots/{$lot->id}")->assertForbidden();
        $this->postJson("/api/v1/retail/inventory-lots/{$lot->id}/reserve", ['quantity' => 1])->assertForbidden();
        $this->postJson('/api/v1/retail/sales', ['retail_location_id' => $otherLocation->id, 'client_reference' => (string) Str::uuid(), 'items' => [['inventory_lot_id' => $lot->id, 'quantity' => 1, 'unit_price' => 1000]]])->assertForbidden();
        $this->getJson('/api/v1/retail/inventory-lots')->assertOk()->assertJsonCount(0, 'data.data');
    }
}
