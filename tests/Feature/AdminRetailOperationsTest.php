<?php

namespace Tests\Feature;

use App\Actions\Retail\AdjustRetailInventory;
use App\Actions\Retail\RecordRetailSale;
use App\Enums\InventoryStatus;
use App\Models\AuditLog;
use App\Models\InventoryLot;
use App\Models\RetailLocation;
use App\Models\RetailReceipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminRetailOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_navigate_retailer_location_receipt_and_inventory(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $retailer = User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $location = RetailLocation::query()->firstOrFail();
        $receipt = RetailReceipt::query()->firstOrFail();
        $lot = InventoryLot::query()->firstOrFail();

        $this->actingAs($admin)->get('/admin/retail/retailers')->assertOk()->assertSee('Amali Jayasinghe')->assertSee('Ocean Fresh Markets');
        $this->actingAs($admin)->get('/admin/retail/retailers/'.$retailer->id)->assertOk()->assertSee('Newest 100 receipts')->assertSee('LBL-DEMO-RETAIL-01');
        $this->actingAs($admin)->get('/admin/retail/locations')->assertOk()->assertSee('Ocean Fresh Colombo')->assertSee('CMB-01');
        $this->actingAs($admin)->get('/admin/retail/locations/'.$location->id)->assertOk()->assertSee('Newest 100 inventory lots')->assertSee('LBL-DEMO-RETAIL-01');
        $this->actingAs($admin)->get('/admin/retail/receipts')->assertOk()->assertSee('Package receipts')->assertSee('FT-DEMO-0001-01');
        $this->actingAs($admin)->get('/admin/retail/receipts/'.$receipt->id)->assertOk()->assertSee('Retail custody receipt')->assertSee('Amali Jayasinghe');
        $this->actingAs($admin)->get('/admin/retail/inventory')->assertOk()->assertSee('LBL-DEMO-RETAIL-01')->assertSee('IN_STOCK');
        $this->actingAs($admin)->get('/admin/retail/inventory/'.$lot->id)->assertOk()->assertSee('Latest 250 immutable stock movements')->assertSee('RECEIVED');
        $this->actingAs($admin)->get('/admin/retail/movements')->assertOk()->assertSee('Stock movement ledger')->assertSee('RECEIVED');
    }

    public function test_admin_can_view_sales_and_stock_reconciliation_without_client_reference(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $retailer = User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $lot = InventoryLot::query()->firstOrFail();
        $reference = (string) Str::uuid();
        $sale = app(RecordRetailSale::class)->execute($retailer, ['retail_location_id' => $lot->retail_location_id, 'client_reference' => $reference, 'items' => [['inventory_lot_id' => $lot->id, 'quantity' => 1, 'unit_price' => 1499.50]]]);

        $this->actingAs($admin)->get('/admin/retail/sales')->assertOk()->assertSee($sale->receipt_number)->assertSee('1499.50')->assertDontSee($reference);
        $this->actingAs($admin)->get('/admin/retail/sales/'.$sale->id)->assertOk()->assertSee('Sale items')->assertSee('LBL-DEMO-RETAIL-01')->assertSee('1499.50')->assertDontSee($reference);
        $this->actingAs($admin)->get('/admin/retail/inventory/'.$lot->id)->assertOk()->assertSee('SOLD')->assertSee($sale->receipt_number);
    }

    public function test_expiry_and_recall_monitoring_surfaces_only_risky_lots(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $retailer = User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $lot = InventoryLot::query()->firstOrFail();
        $lot->update(['expires_at' => now()->addDays(3)]);

        $this->actingAs($admin)->get('/admin/retail/risks')->assertOk()->assertSee('EXPIRING SOON')->assertSee('LBL-DEMO-RETAIL-01');
        app(AdjustRetailInventory::class)->markUnavailable($retailer, $lot->fresh(), InventoryStatus::RECALLED, 'Supplier recall for admin monitoring.');
        $this->actingAs($admin)->get('/admin/retail/risks')->assertOk()->assertSee('RECALL');
        $this->actingAs($admin)->get('/admin/retail/inventory/'.$lot->id)->assertOk()->assertSee('Supplier recall for admin monitoring.');
        $this->actingAs($admin)->get('/admin/retail/inventory?status=RECALLED')->assertOk()->assertSee('LBL-DEMO-RETAIL-01');
    }

    public function test_inventory_and_sales_exports_are_filtered_private_and_audited(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $retailer = User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $lot = InventoryLot::query()->firstOrFail();
        $reference = (string) Str::uuid();
        $sale = app(RecordRetailSale::class)->execute($retailer, ['retail_location_id' => $lot->retail_location_id, 'client_reference' => $reference, 'items' => [['inventory_lot_id' => $lot->id, 'quantity' => 1, 'unit_price' => 1250]]]);

        $inventory = $this->actingAs($admin)->get('/admin/retail/inventory/export?status=IN_STOCK');
        $inventory->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('LBL-DEMO-RETAIL-01', $inventory->streamedContent());
        $this->assertStringNotContainsString('public_token', $inventory->streamedContent());
        $sales = $this->actingAs($admin)->get('/admin/retail/sales/export?q='.$sale->receipt_number);
        $sales->assertOk();
        $this->assertStringContainsString($sale->receipt_number, $sales->streamedContent());
        $this->assertStringNotContainsString($reference, $sales->streamedContent());
        $this->assertTrue(AuditLog::query()->where('action', 'RETAIL_INVENTORY_EXPORTED')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'RETAIL_SALES_EXPORTED')->exists());
    }

    public function test_non_admin_cannot_access_retail_operations(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $location = RetailLocation::query()->firstOrFail();
        $receipt = RetailReceipt::query()->firstOrFail();
        $lot = InventoryLot::query()->firstOrFail();
        $sale = app(RecordRetailSale::class)->execute($user, ['retail_location_id' => $lot->retail_location_id, 'client_reference' => (string) Str::uuid(), 'items' => [['inventory_lot_id' => $lot->id, 'quantity' => 1, 'unit_price' => 1000]]]);
        $urls = ['/admin/retail/retailers', '/admin/retail/retailers/'.$user->id, '/admin/retail/locations', '/admin/retail/locations/'.$location->id, '/admin/retail/receipts', '/admin/retail/receipts/'.$receipt->id, '/admin/retail/inventory', '/admin/retail/inventory/'.$lot->id, '/admin/retail/inventory/export', '/admin/retail/movements', '/admin/retail/sales', '/admin/retail/sales/export', '/admin/retail/risks'];
        $urls[] = '/admin/retail/sales/'.$sale->id;
        foreach ($urls as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }
}
