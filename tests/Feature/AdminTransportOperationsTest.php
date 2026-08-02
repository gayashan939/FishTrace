<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ColdChainAlert;
use App\Models\FirebaseSyncCursor;
use App\Models\FirebaseSyncFailure;
use App\Models\FishBatch;
use App\Models\IotDevice;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTransportOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_navigate_transporters_vehicles_trips_and_devices(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $transporter = User::query()->where('email', 'transporter@fishtrace.demo')->firstOrFail();
        $vehicle = Vehicle::query()->firstOrFail();
        $trip = TransportTrip::query()->firstOrFail();
        $device = IotDevice::query()->firstOrFail();

        $this->actingAs($admin)->get('/admin/transport/transporters')->assertOk()->assertSee('Kasun Silva');
        $this->actingAs($admin)->get('/admin/transport/transporters/'.$transporter->id)->assertOk()->assertSee('TTR-DEMO-001');
        $this->actingAs($admin)->get('/admin/transport/vehicles')->assertOk()->assertSee('WP-CAB-2048');
        $this->actingAs($admin)->get('/admin/transport/vehicles/'.$vehicle->id)->assertOk()->assertSee('Newest 100 trips');
        $this->actingAs($admin)->get('/admin/transport/trips')->assertOk()->assertSee('Mirissa')->assertSee('Colombo');
        $this->actingAs($admin)->get('/admin/transport/trips/'.$trip->id)->assertOk()->assertSee('Latest 250 permanent readings')->assertSee('Reefer Sensor 01')->assertSee('FT-DEMO-0001');
        $this->actingAs($admin)->get('/admin/transport/devices')->assertOk()->assertSee('IOT-001');
        $this->actingAs($admin)->get('/admin/transport/devices/'.$device->id)->assertOk()->assertSee('Capabilities')->assertSee('Assignment history');
    }

    public function test_telemetry_page_excludes_raw_payloads_and_coordinates(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();

        $this->actingAs($admin)->get('/admin/transport/telemetry')->assertOk()->assertSee('Permanent MySQL history')->assertSee('IOT-001')->assertDontSee('raw_payload')->assertDontSee('latitude')->assertDontSee('5.9500000');
    }

    public function test_alert_and_sync_health_pages_exclude_firebase_secrets_and_payloads(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $trip = TransportTrip::query()->firstOrFail();
        $device = IotDevice::query()->firstOrFail();
        $batch = FishBatch::query()->where('batch_code', 'FT-DEMO-0001')->firstOrFail();
        $alert = ColdChainAlert::create(['transport_trip_id' => $trip->id, 'fish_batch_id' => $batch->id, 'type' => 'CRITICAL_TEMPERATURE', 'severity' => 'CRITICAL', 'status' => 'OPEN', 'measured_value' => 9.2, 'threshold_value' => 8, 'first_detected_at' => now(), 'last_detected_at' => now()]);
        FirebaseSyncCursor::create(['device_id' => $device->id, 'last_message_id' => 'msg-safe-001', 'last_successful_sync_at' => now(), 'last_error' => 'Previous timeout']);
        FirebaseSyncFailure::create(['device_id' => $device->id, 'firebase_uid' => 'secret-firebase-uid', 'message_id' => 'failed-001', 'payload' => ['secret' => 'payload-must-not-render'], 'error_code' => 'INVALID_SCHEMA', 'error_message' => 'Schema rejected', 'retry_count' => 2, 'first_failed_at' => now(), 'last_failed_at' => now()]);

        $this->actingAs($admin)->get('/admin/transport/alerts')->assertOk()->assertSee('CRITICAL TEMPERATURE')->assertSee('TTR-DEMO-001');
        $this->actingAs($admin)->get('/admin/transport/alerts/'.$alert->id)->assertOk()->assertSee('WP-CAB-2048')->assertSee('FT-DEMO-0001');
        $this->actingAs($admin)->get('/admin/transport/sync-health')->assertOk()->assertSee('INVALID_SCHEMA')->assertSee('Schema rejected')->assertDontSee('secret-firebase-uid')->assertDontSee('payload-must-not-render');
    }

    public function test_telemetry_and_alert_exports_are_filtered_and_audited(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $trip = TransportTrip::query()->firstOrFail();
        ColdChainAlert::create(['transport_trip_id' => $trip->id, 'type' => 'LOW_BATTERY', 'severity' => 'WARNING', 'status' => 'OPEN', 'measured_value' => 15, 'threshold_value' => 20, 'first_detected_at' => now(), 'last_detected_at' => now()]);

        $telemetry = $this->actingAs($admin)->get('/admin/transport/telemetry/export?trip_id='.$trip->id);
        $telemetry->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('IOT-001', $telemetry->streamedContent());
        $this->assertStringNotContainsString('latitude', $telemetry->streamedContent());
        $alerts = $this->actingAs($admin)->get('/admin/transport/alerts/export?severity=WARNING');
        $alerts->assertOk();
        $this->assertStringContainsString('LOW_BATTERY', $alerts->streamedContent());
        $this->assertTrue(AuditLog::query()->where('action', 'TELEMETRY_DIRECTORY_EXPORTED')->exists());
        $this->assertTrue(AuditLog::query()->where('action', 'COLD_CHAIN_ALERTS_EXPORTED')->exists());
    }

    public function test_non_admin_cannot_access_transport_operations(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'transporter@fishtrace.demo')->firstOrFail();
        $trip = TransportTrip::query()->firstOrFail();
        $device = IotDevice::query()->firstOrFail();

        foreach (['/admin/transport/transporters', '/admin/transport/vehicles', '/admin/transport/trips', '/admin/transport/trips/'.$trip->id, '/admin/transport/devices', '/admin/transport/devices/'.$device->id, '/admin/transport/telemetry', '/admin/transport/alerts', '/admin/transport/sync-health', '/admin/transport/telemetry/export', '/admin/transport/alerts/export'] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }
}
