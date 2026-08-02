<?php

namespace Tests\Feature;

use App\Models\AlertRuleConfig;
use App\Models\AuditLog;
use App\Models\ColdChainAlert;
use App\Models\IotDevice;
use App\Models\SensorReading;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\IoT\ColdChainEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminAlertRuleSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_update_validated_audited_alert_rules(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $this->actingAs($admin)->get('/admin/settings/alert-rules')->assertOk()->assertSee('Cold-chain alert rules')->assertSee('Product temperature')->assertSee('Save alert rules');

        $payload = $this->rules();
        $payload['HIGH_TEMPERATURE']['warning_threshold'] = 5;
        $payload['HIGH_TEMPERATURE']['critical_threshold'] = 7;
        $this->put('/admin/settings/alert-rules', ['rules' => $payload])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseCount('alert_rule_configs', 6);
        $this->assertDatabaseHas('alert_rule_configs', ['scope_key' => 'GLOBAL:HIGH_TEMPERATURE', 'warning_threshold' => 5, 'critical_threshold' => 7]);
        $this->assertSame(6, AuditLog::query()->where('action', 'ALERT_RULE_UPDATED')->where('user_id', $admin->id)->count());

        $payload['HIGH_TEMPERATURE']['critical_threshold'] = 4;
        $this->from('/admin/settings/alert-rules')->put('/admin/settings/alert-rules', ['rules' => $payload])->assertRedirect('/admin/settings/alert-rules')->assertSessionHasErrors('rules.HIGH_TEMPERATURE.critical_threshold');
        $this->assertSame('7.000', AlertRuleConfig::query()->where('rule_type', 'HIGH_TEMPERATURE')->value('critical_threshold'));
    }

    public function test_configured_thresholds_and_duration_drive_real_alerts(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $payload = $this->rules();
        $payload['HIGH_TEMPERATURE'] = ['warning_threshold' => 5, 'critical_threshold' => 6, 'duration_minutes' => 10, 'is_enabled' => true];
        $this->actingAs($admin)->put('/admin/settings/alert-rules', ['rules' => $payload])->assertRedirect();
        $trip = TransportTrip::query()->where('status', 'ACTIVE')->firstOrFail();
        $device = IotDevice::query()->firstOrFail();
        SensorReading::query()->where('transport_trip_id', $trip->id)->delete();

        $critical = $this->reading($trip, $device, ['product_temperature' => 6.5, 'recorded_at' => now()->subMinutes(20)]);
        app(ColdChainEvaluator::class)->evaluate($critical);
        $this->assertDatabaseHas('cold_chain_alerts', ['type' => 'CRITICAL_TEMPERATURE', 'severity' => 'CRITICAL', 'threshold_value' => 6]);

        $recovered = $this->reading($trip, $device, ['product_temperature' => 3, 'recorded_at' => now()->subMinutes(15)]);
        app(ColdChainEvaluator::class)->evaluate($recovered);
        $this->assertDatabaseHas('cold_chain_alerts', ['type' => 'CRITICAL_TEMPERATURE', 'status' => 'RESOLVED']);
        $this->reading($trip, $device, ['product_temperature' => 5.5, 'recorded_at' => now()->subMinutes(11)]);
        $current = $this->reading($trip, $device, ['product_temperature' => 5.5, 'recorded_at' => now()]);
        app(ColdChainEvaluator::class)->evaluate($current);
        $this->assertDatabaseHas('cold_chain_alerts', ['type' => 'HIGH_TEMPERATURE', 'severity' => 'WARNING', 'threshold_value' => 5]);
    }

    public function test_low_temperature_battery_gps_and_door_rules_can_be_enforced_or_disabled(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $payload = $this->rules();
        $payload['LOW_TEMPERATURE']['warning_threshold'] = 1;
        $this->actingAs($admin)->put('/admin/settings/alert-rules', ['rules' => $payload])->assertRedirect();
        $trip = TransportTrip::query()->where('status', 'ACTIVE')->firstOrFail();
        $device = IotDevice::query()->firstOrFail();
        $reading = $this->reading($trip, $device, ['product_temperature' => 0, 'battery_percentage' => 5, 'latitude' => null, 'longitude' => null, 'door_open' => true, 'recorded_at' => now()]);
        app(ColdChainEvaluator::class)->evaluate($reading);

        foreach (['LOW_TEMPERATURE', 'LOW_BATTERY', 'GPS_UNAVAILABLE', 'DOOR_OPENED'] as $type) {
            $this->assertDatabaseHas('cold_chain_alerts', ['transport_trip_id' => $trip->id, 'type' => $type, 'status' => 'OPEN']);
        }
        $this->assertDatabaseHas('cold_chain_alerts', ['type' => 'LOW_BATTERY', 'severity' => 'CRITICAL', 'threshold_value' => 10]);

        $payload['LOW_BATTERY']['is_enabled'] = false;
        $payload['GPS_UNAVAILABLE']['is_enabled'] = false;
        $payload['DOOR_OPENED']['is_enabled'] = false;
        $this->actingAs($admin)->put('/admin/settings/alert-rules', ['rules' => $payload])->assertRedirect();
        app(ColdChainEvaluator::class)->evaluate($reading);
        foreach (['LOW_BATTERY', 'GPS_UNAVAILABLE', 'DOOR_OPENED'] as $type) {
            $this->assertDatabaseHas('cold_chain_alerts', ['transport_trip_id' => $trip->id, 'type' => $type, 'status' => 'RESOLVED']);
        }
    }

    public function test_offline_rule_and_acknowledgement_follow_configuration_and_authorization(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $payload = $this->rules();
        $payload['DEVICE_OFFLINE'] = ['warning_threshold' => null, 'critical_threshold' => null, 'duration_minutes' => 5, 'is_enabled' => true];
        $this->actingAs($admin)->put('/admin/settings/alert-rules', ['rules' => $payload])->assertRedirect();
        $device = IotDevice::query()->firstOrFail();
        $device->update(['last_seen_at' => now()->subMinutes(10)]);
        $this->artisan('fishtrace:check-offline-devices')->assertSuccessful();
        $alert = ColdChainAlert::query()->where('type', 'DEVICE_OFFLINE')->firstOrFail();
        $this->assertSame(5.0, (float) $alert->threshold_value);

        $transporter = User::query()->where('email', 'transporter@fishtrace.demo')->firstOrFail();
        $this->actingAs($transporter)->postJson('/api/v1/alerts/'.$alert->id.'/acknowledge', ['note' => 'Driver contacted.'])->assertOk()->assertJsonPath('data.status', 'ACKNOWLEDGED');
        $this->assertDatabaseHas('alert_acknowledgements', ['cold_chain_alert_id' => $alert->id, 'user_id' => $transporter->id, 'note' => 'Driver contacted.']);
        $this->assertTrue(AuditLog::query()->where('action', 'COLD_CHAIN_ALERT_ACKNOWLEDGED')->where('auditable_id', $alert->id)->exists());
        $this->postJson('/api/v1/alerts/'.$alert->id.'/acknowledge')->assertStatus(409);

        $device->update(['last_seen_at' => now()]);
        $this->artisan('fishtrace:check-offline-devices')->assertSuccessful();
        $this->assertDatabaseHas('cold_chain_alerts', ['id' => $alert->id, 'status' => 'RESOLVED']);

        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $this->actingAs($fisher)->get('/admin/settings/alert-rules')->assertForbidden();
        $this->put('/admin/settings/alert-rules', ['rules' => $payload])->assertForbidden();
    }

    private function rules(): array
    {
        return [
            'HIGH_TEMPERATURE' => ['warning_threshold' => 4, 'critical_threshold' => 8, 'duration_minutes' => 10, 'is_enabled' => true],
            'LOW_TEMPERATURE' => ['warning_threshold' => 0, 'critical_threshold' => null, 'duration_minutes' => 0, 'is_enabled' => true],
            'LOW_BATTERY' => ['warning_threshold' => 20, 'critical_threshold' => 10, 'duration_minutes' => 0, 'is_enabled' => true],
            'DEVICE_OFFLINE' => ['warning_threshold' => null, 'critical_threshold' => null, 'duration_minutes' => 15, 'is_enabled' => true],
            'GPS_UNAVAILABLE' => ['warning_threshold' => null, 'critical_threshold' => null, 'duration_minutes' => 0, 'is_enabled' => true],
            'DOOR_OPENED' => ['warning_threshold' => null, 'critical_threshold' => null, 'duration_minutes' => 0, 'is_enabled' => true],
        ];
    }

    private function reading(TransportTrip $trip, IotDevice $device, array $overrides): SensorReading
    {
        return SensorReading::create(array_merge(['message_id' => (string) Str::uuid(), 'iot_device_id' => $device->id, 'transport_trip_id' => $trip->id, 'product_temperature' => 3, 'battery_percentage' => 80, 'latitude' => 6.9, 'longitude' => 79.8, 'door_open' => false, 'recorded_at' => now(), 'imported_at' => now(), 'raw_payload' => ['schemaVersion' => 1]], $overrides));
    }
}
