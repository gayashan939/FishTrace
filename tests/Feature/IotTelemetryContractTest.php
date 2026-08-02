<?php

namespace Tests\Feature;

use App\Models\IotDevice;
use App\Models\TransportTrip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IotTelemetryContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_transport_telemetry_responses_are_whitelisted_and_include_operational_context(): void
    {
        $this->seed();
        $transporter = User::query()->where('email', 'transporter@fishtrace.demo')->firstOrFail();
        $trip = TransportTrip::query()
            ->where('organization_id', $transporter->primaryOrganization()?->id)
            ->whereHas('readings')
            ->firstOrFail();
        Sanctum::actingAs($transporter);

        $latest = $this->getJson("/api/v1/transport-trips/{$trip->id}/sensor-readings/latest")
            ->assertOk()
            ->assertJsonStructure(['data' => [
                'id', 'message_id', 'iot_device_id', 'transport_trip_id',
                'product_temperature', 'air_temperature', 'humidity',
                'latitude', 'longitude', 'speed_kph', 'battery_percentage',
                'signal_strength', 'door_open', 'recorded_at', 'imported_at',
                'reading_age_seconds', 'device_status', 'active_alert_count',
            ]]);
        $this->assertStringNotContainsString('raw_payload', $latest->getContent());

        $readings = $this->getJson("/api/v1/transport-trips/{$trip->id}/sensor-readings?per_page=2")
            ->assertOk()
            ->assertJsonCount(2, 'data.data');
        $this->assertStringNotContainsString('raw_payload', $readings->getContent());

        $summary = $this->getJson("/api/v1/transport-trips/{$trip->id}/sensor-summary?period=hour")
            ->assertOk()
            ->assertJsonPath('data.period', 'hour')
            ->assertJsonStructure(['data' => ['period_start', 'reading_count', 'latest_reading' => ['reading_age_seconds', 'device_status']]]);
        $this->assertStringNotContainsString('raw_payload', $summary->getContent());

        $live = $this->getJson("/api/v1/transport-trips/{$trip->id}/live-access")
            ->assertOk()
            ->assertJsonStructure(['data' => ['path', 'trip_id', 'assignment_status', 'device', 'latest_mysql_reading', 'firebase_session_required']]);
        $this->assertStringNotContainsString('raw_payload', $live->getContent());
    }

    public function test_device_telemetry_responses_are_whitelisted(): void
    {
        $this->seed();
        Sanctum::actingAs(User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail());
        $device = IotDevice::query()->whereHas('readings')->firstOrFail();

        foreach ([
            "/api/v1/iot/devices/{$device->id}/readings?per_page=2",
            "/api/v1/iot/devices/{$device->id}/latest-reading",
            "/api/v1/iot/devices/{$device->id}/health",
        ] as $uri) {
            $response = $this->getJson($uri)->assertOk();
            $this->assertStringNotContainsString('raw_payload', $response->getContent());
        }

        $this->getJson("/api/v1/iot/devices/{$device->id}/latest-reading")
            ->assertJsonStructure(['data' => ['reading_age_seconds', 'device_status']]);
    }

    public function test_telemetry_and_alert_filters_reject_invalid_values(): void
    {
        $this->seed();
        $transporter = User::query()->where('email', 'transporter@fishtrace.demo')->firstOrFail();
        $trip = TransportTrip::query()->where('organization_id', $transporter->primaryOrganization()?->id)->firstOrFail();
        Sanctum::actingAs($transporter);

        $this->getJson("/api/v1/transport-trips/{$trip->id}/sensor-readings?date_from=not-a-date")->assertUnprocessable();
        $this->getJson("/api/v1/transport-trips/{$trip->id}/sensor-readings?per_page=101")->assertUnprocessable();
        $this->getJson("/api/v1/transport-trips/{$trip->id}/sensor-summary?period=week")->assertUnprocessable();
        $this->getJson("/api/v1/transport-trips/{$trip->id}/alerts?status=INVALID")->assertUnprocessable();
        $this->getJson("/api/v1/transport-trips/{$trip->id}/alerts?severity=EMERGENCY")->assertUnprocessable();

        Sanctum::actingAs(User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail());
        $device = IotDevice::query()->firstOrFail();
        $this->getJson("/api/v1/iot/devices/{$device->id}/readings?date_to=not-a-date")->assertUnprocessable();
        $this->getJson("/api/v1/iot/devices/{$device->id}/readings?per_page=0")->assertUnprocessable();
    }
}
