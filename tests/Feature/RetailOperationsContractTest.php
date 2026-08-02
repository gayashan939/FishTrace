<?php

namespace Tests\Feature;

use App\Models\ColdChainAlert;
use App\Models\InventoryLot;
use App\Models\TransportTrip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RetailOperationsContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_adjustments_preserve_inventory_reconciliation_and_require_a_reason(): void
    {
        $this->seed();
        $retailer = User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $lot = InventoryLot::query()->where('organization_id', $retailer->primaryOrganization()?->id)->firstOrFail();
        Sanctum::actingAs($retailer);

        $this->postJson('/api/v1/retailer/stock-adjustments', ['inventory_lot_id' => $lot->id, 'direction' => 'REMOVE', 'quantity' => 1])->assertUnprocessable();
        $this->postJson('/api/v1/retailer/stock-adjustments', ['inventory_lot_id' => $lot->id, 'direction' => 'REMOVE', 'quantity' => 1, 'reason' => 'Damaged package removed during count.'])
            ->assertOk()->assertJsonPath('data.total_packages', 3)->assertJsonPath('data.available_packages', 3);
        $this->postJson('/api/v1/retailer/stock-adjustments', ['inventory_lot_id' => $lot->id, 'direction' => 'ADD', 'quantity' => 2, 'reason' => 'Two verified packages found during recount.'])
            ->assertOk()->assertJsonPath('data.total_packages', 5)->assertJsonPath('data.available_packages', 5);
        $this->postJson('/api/v1/retailer/stock-adjustments', ['inventory_lot_id' => $lot->id, 'direction' => 'REMOVE', 'quantity' => 6, 'reason' => 'Invalid excessive adjustment attempt.'])->assertUnprocessable();

        $fresh = $lot->fresh();
        $this->assertSame($fresh->total_packages, $fresh->available_packages + $fresh->reserved_packages + $fresh->sold_packages);
        $this->assertDatabaseHas('stock_movements', ['inventory_lot_id' => $lot->id, 'type' => 'ADJUSTED_OUT', 'quantity' => 1]);
        $this->assertDatabaseHas('stock_movements', ['inventory_lot_id' => $lot->id, 'type' => 'ADJUSTED_IN', 'quantity' => 2]);
    }

    public function test_retail_alerts_are_inventory_scoped_quarantined_and_resolved(): void
    {
        $this->seed();
        $retailer = User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail();
        $lot = InventoryLot::query()->where('organization_id', $retailer->primaryOrganization()?->id)->firstOrFail();
        $alert = ColdChainAlert::query()->create([
            'transport_trip_id' => TransportTrip::query()->firstOrFail()->id,
            'fish_batch_id' => $lot->fish_batch_id,
            'type' => 'RETAIL_TEMPERATURE_RECALL',
            'severity' => 'CRITICAL',
            'status' => 'OPEN',
            'measured_value' => 9.2,
            'threshold_value' => 8,
            'first_detected_at' => now(),
            'last_detected_at' => now(),
        ]);
        Sanctum::actingAs($retailer);

        $this->getJson('/api/v1/retailer/alerts?status=OPEN')->assertOk()->assertJsonPath('data.data.0.id', $alert->id);
        $this->postJson("/api/v1/retailer/recalls/{$alert->id}/quarantine", ['reason' => 'Cold-chain alert requires immediate quarantine.'])
            ->assertOk()->assertJsonPath('data.alert.status', 'ACKNOWLEDGED')->assertJsonPath('data.inventory_lots.0.status', 'RECALLED');
        $this->postJson("/api/v1/retailer/alerts/{$alert->id}/resolve", ['note' => 'Affected stock quarantined and investigation completed.'])
            ->assertOk()->assertJsonPath('data.status', 'RESOLVED');
        $this->postJson("/api/v1/retailer/alerts/{$alert->id}/resolve", ['note' => 'Duplicate resolution attempt.'])->assertConflict();

        $this->assertDatabaseHas('inventory_lots', ['id' => $lot->id, 'status' => 'RECALLED']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'RETAIL_ALERT_RESOLVED', 'user_id' => $retailer->id]);
    }

    public function test_retail_report_aliases_use_the_existing_scoped_report_engine(): void
    {
        $this->seed();
        Sanctum::actingAs(User::query()->where('email', 'retailer@fishtrace.demo')->firstOrFail());

        $this->getJson('/api/v1/retailer/reports/summary')->assertOk()->assertJsonPath('data.available_inventory_packages', 4);
        $this->getJson('/api/v1/retailer/reports/inventory')->assertOk()->assertJsonPath('data.report', 'inventory')->assertJsonPath('data.row_count', 1);
        $this->getJson('/api/v1/retailer/reports/sales')->assertOk()->assertJsonPath('data.report', 'sales');
    }
}
