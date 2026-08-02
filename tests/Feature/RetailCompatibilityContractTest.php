<?php

namespace Tests\Feature;

use App\Models\PackageLabel;
use App\Models\RetailLocation;
use App\Models\RetailSale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RetailCompatibilityContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_retail_directories_are_bounded_and_validate_inventory_filters(): void
    {
        $this->seed();
        Sanctum::actingAs(User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail());

        foreach (['retail/incoming-labels', 'retailer/receipts', 'retailer/inventory', 'retailer/sales', 'retailer/alerts'] as $directory) {
            $this->getJson("/api/v1/{$directory}?per_page=101")
                ->assertUnprocessable()
                ->assertJsonPath('error.code', 'VALIDATION_FAILED')
                ->assertJsonStructure(['error' => ['field_errors' => ['per_page']]]);
        }

        $this->getJson('/api/v1/retailer/inventory?status=UNKNOWN&location_id=not-a-uuid')
            ->assertUnprocessable()
            ->assertJsonStructure(['error' => ['field_errors' => ['status', 'location_id']]]);
    }

    public function test_original_retailer_receipt_and_inventory_paths_are_functional(): void
    {
        $this->seed();
        $retailer = User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $location = RetailLocation::query()->where('organization_id', $retailer->primaryOrganization()?->id)->firstOrFail();
        $label = PackageLabel::query()->whereDoesntHave('retailReceipt')->firstOrFail();
        Sanctum::actingAs($retailer);

        $lot = $this->postJson('/api/v1/retailer/receipts', [
            'package_label_id' => $label->id,
            'retail_location_id' => $location->id,
            'received_package_count' => $label->package_count,
            'condition_temperature' => -1.2,
        ])->assertCreated()->json('data');

        $receiptId = $lot['retail_receipt_id'];
        $this->getJson('/api/v1/retailer/dashboard')->assertOk();
        $this->getJson('/api/v1/retailer/receipts')->assertOk()->assertJsonPath('data.data.0.id', $receiptId);
        $this->getJson("/api/v1/retailer/receipts/{$receiptId}")->assertOk()->assertJsonPath('data.inventory_lot.id', $lot['id']);
        $this->getJson('/api/v1/retailer/inventory')->assertOk()->assertJsonFragment(['id' => $lot['id']]);
        $this->getJson("/api/v1/retailer/inventory/{$lot['id']}")->assertOk()->assertJsonPath('data.id', $lot['id']);
    }

    public function test_original_retailer_sale_paths_preserve_strict_idempotency(): void
    {
        $this->seed();
        $retailer = User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $location = RetailLocation::query()->where('organization_id', $retailer->primaryOrganization()?->id)->firstOrFail();
        $lot = $location->inventoryLots()->firstOrFail();
        Sanctum::actingAs($retailer);
        $payload = [
            'retail_location_id' => $location->id,
            'client_reference' => (string) Str::uuid(),
            'items' => [['inventory_lot_id' => $lot->id, 'quantity' => 1, 'unit_price' => 1000]],
        ];

        $created = $this->postJson('/api/v1/retailer/sales', $payload)
            ->assertCreated()
            ->assertJsonMissingPath('data.request_fingerprint')
            ->assertDontSee('request_fingerprint');
        $sale = $created->json('data');
        $this->postJson('/api/v1/retailer/sales', $payload)
            ->assertCreated()
            ->assertJsonPath('data.id', $sale['id'])
            ->assertJsonMissingPath('data.request_fingerprint');
        $payload['items'][0]['quantity'] = 2;
        $this->postJson('/api/v1/retailer/sales', $payload)->assertConflict();
        $this->getJson('/api/v1/retailer/sales')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $sale['id'])
            ->assertDontSee('request_fingerprint');
        $this->getJson("/api/v1/retailer/sales/{$sale['id']}")
            ->assertOk()
            ->assertJsonPath('data.id', $sale['id'])
            ->assertJsonMissingPath('data.request_fingerprint');

        $this->assertDatabaseCount('retail_sales', 1);
        $this->assertDatabaseHas('inventory_lots', ['id' => $lot->id, 'sold_packages' => 1]);
        $this->assertNotNull(RetailSale::query()->findOrFail($sale['id'])->request_fingerprint);
    }
}
