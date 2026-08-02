<?php

namespace Tests\Feature;

use App\Contracts\Firebase\FirebaseRealtimeClient;
use App\Models\ColdChainAlert;
use App\Models\IotDevice;
use App\Models\SensorReading;
use App\Models\TransportTrip;
use App\Services\IoT\TelemetryImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelemetryAndTraceTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_import_is_idempotent_and_marked_synchronized(): void
    {
        $this->seed();
        $device = IotDevice::firstOrFail();
        $trip = TransportTrip::firstOrFail();
        $payload = ['tripId' => $trip->id, 'productTemperature' => 4.1, 'airTemperature' => 4.8, 'humidity' => 82, 'latitude' => 6.9271, 'longitude' => 79.8612, 'batteryPercentage' => 88, 'recordedAt' => now()->getTimestampMs(), 'schemaVersion' => 1];
        $importer = app(TelemetryImporter::class);
        $first = $importer->import($device, 'immutable-message-1', $payload);
        $second = $importer->import($device, 'immutable-message-1', $payload);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, SensorReading::where('message_id', 'immutable-message-1')->count());
        $stored = app(FirebaseRealtimeClient::class)->get('telemetry/'.$device->firebase_uid.'/immutable-message-1');
        $this->assertSame('SYNCED', $stored['syncStatus']);
    }

    public function test_public_trace_is_available_and_privacy_filtered(): void
    {
        $this->seed();
        $response = $this->getJson('/api/v1/public/trace/demo-trace-yellowfin-tuna-2026')->assertOk()->assertJsonPath('data.verification_status', 'VERIFIED')->assertJsonPath('data.batch.species', 'Yellowfin Tuna');
        $json = $response->getContent();
        foreach (['firebase_uid', 'firebase_password', 'phone', 'private_data', 'raw_payload'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $json);
        }
        $this->get('/trace/demo-trace-yellowfin-tuna-2026')->assertOk()->assertSee('FT-DEMO-0001');
    }

    public function test_cold_chain_alerts_are_deduplicated_and_resolved_on_recovery(): void
    {
        $this->seed();
        $device = IotDevice::firstOrFail();
        $trip = TransportTrip::firstOrFail();
        $importer = app(TelemetryImporter::class);
        $payload = ['tripId' => $trip->id, 'productTemperature' => 9.2, 'batteryPercentage' => 80, 'recordedAt' => now()->getTimestampMs(), 'schemaVersion' => 1];
        $importer->import($device, 'critical-1', $payload);
        $payload['recordedAt'] = now()->addMinute()->getTimestampMs();
        $importer->import($device, 'critical-2', $payload);
        $this->assertSame(1, ColdChainAlert::where('type', 'CRITICAL_TEMPERATURE')->where('status', 'OPEN')->count());
        $payload['productTemperature'] = 3.5;
        $payload['recordedAt'] = now()->addMinutes(2)->getTimestampMs();
        $importer->import($device, 'recovered-1', $payload);
        $this->assertDatabaseHas('cold_chain_alerts', ['type' => 'CRITICAL_TEMPERATURE', 'status' => 'RESOLVED']);
    }
}
