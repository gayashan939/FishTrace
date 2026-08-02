<?php

namespace Tests\Feature;

use App\Models\AIPrediction;
use App\Models\ColdChainAlert;
use App\Models\FirebaseSyncFailure;
use App\Models\InventoryLot;
use App\Models\IotDevice;
use App\Models\QualityInspection;
use App\Models\ReportExport;
use App\Models\TransportTrip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_consolidates_all_operational_domains(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();

        $this->actingAs($admin)->get('/admin')->assertOk()
            ->assertSee('Supply-chain activity')->assertSee('Risk and compliance')->assertSee('Available retail packages')
            ->assertSee('Quality incident queue')->assertSee('Cold-chain alerts')->assertSee('Expiry and recall exposure')
            ->assertSee('Device and sync health')->assertSee('Report job attention')->assertSee('Latest permanent telemetry')
            ->assertSee('FT-DEMO-0001')->assertSee('IOT-001')->assertSee('TTR-DEMO-001')
            ->assertDontSee('raw_payload')->assertDontSee('latitude')->assertDontSee('5.9500000');
    }

    public function test_dashboard_surfaces_cross_module_risk_and_withholds_failure_secrets(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $passed = QualityInspection::query()->firstOrFail();
        QualityInspection::create(['fish_batch_id' => $passed->fish_batch_id, 'processing_record_id' => $passed->processing_record_id, 'organization_id' => $passed->organization_id, 'inspector_id' => $passed->inspector_id, 'result' => 'FAILED', 'product_temperature' => 8.1, 'appearance' => 'Dashboard incident', 'odor' => 'Unacceptable', 'notes' => 'Review now.', 'inspected_at' => now()]);
        $trip = TransportTrip::query()->firstOrFail();
        ColdChainAlert::create(['transport_trip_id' => $trip->id, 'type' => 'DASHBOARD_TEMPERATURE', 'severity' => 'CRITICAL', 'status' => 'OPEN', 'measured_value' => 9, 'threshold_value' => 8, 'first_detected_at' => now(), 'last_detected_at' => now()]);
        $lot = InventoryLot::query()->firstOrFail();
        $lot->update(['expires_at' => now()->addDays(2)]);
        $device = IotDevice::query()->firstOrFail();
        FirebaseSyncFailure::create(['device_id' => $device->id, 'firebase_uid' => 'dashboard-secret-uid', 'message_id' => 'dashboard-failure', 'payload' => ['secret' => 'dashboard-secret-payload'], 'error_code' => 'TIMEOUT', 'error_message' => 'dashboard-secret-error', 'retry_count' => 1, 'first_failed_at' => now(), 'last_failed_at' => now()]);
        AIPrediction::create(['fish_batch_id' => $passed->fish_batch_id, 'requested_by' => $admin->id, 'risk_level' => 'HIGH', 'confidence' => .91, 'probabilities' => ['HIGH' => .91], 'recommendation' => 'Inspect', 'model_version' => 'dashboard-v1', 'provider' => 'mock', 'predicted_at' => now()]);
        ReportExport::create(['organization_id' => $admin->primaryOrganization()?->id, 'requested_by' => $admin->id, 'report_type' => 'recalls', 'filters' => ['secret' => 'dashboard-filter-secret'], 'status' => 'FAILED', 'failure_message' => 'dashboard-report-secret']);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Dashboard incident')->assertSee('DASHBOARD TEMPERATURE')->assertSee('1 failures')->assertSee('recalls')->assertSee('FAILED')->assertDontSee('dashboard-secret-uid')->assertDontSee('dashboard-secret-payload')->assertDontSee('dashboard-secret-error')->assertDontSee('dashboard-filter-secret')->assertDontSee('dashboard-report-secret');
    }

    public function test_dashboard_queues_are_bounded_and_query_count_is_controlled(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $trip = TransportTrip::query()->firstOrFail();
        foreach (range(1, 7) as $number) {
            ColdChainAlert::create(['transport_trip_id' => $trip->id, 'type' => 'QUEUE_ALERT_'.$number, 'severity' => 'WARNING', 'status' => 'OPEN', 'measured_value' => $number, 'threshold_value' => 1, 'first_detected_at' => now()->addSeconds($number), 'last_detected_at' => now()->addSeconds($number)]);
        }
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $response = $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('QUEUE ALERT 7')->assertSee('QUEUE ALERT 3')->assertDontSee('QUEUE ALERT 2')->assertDontSee('QUEUE ALERT 1');
        $this->assertLessThanOrEqual(50, $queries);
        $this->assertStringContainsString('/admin/transport/alerts', $response->getContent());
        $this->assertStringContainsString('/admin/compliance/incidents', $response->getContent());
        $this->assertStringContainsString('/admin/retail/risks', $response->getContent());
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'inspector@fishtrace.demo')->firstOrFail();
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
