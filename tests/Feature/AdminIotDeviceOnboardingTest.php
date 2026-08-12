<?php

namespace Tests\Feature;

use App\Contracts\Firebase\FirebaseDeviceAuth;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminIotDeviceOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_and_provision_a_device_from_the_portal(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $organization = Organization::query()->where('type', 'TRANSPORTER')->firstOrFail();
        $firebase = Mockery::mock(FirebaseDeviceAuth::class);
        $firebase->shouldReceive('upsert')->once();
        app()->instance(FirebaseDeviceAuth::class, $firebase);

        $this->actingAs($admin)
            ->get('/admin/transport/devices/create')
            ->assertOk()
            ->assertSee('Add IoT device');

        $response = $this->actingAs($admin)->post('/admin/transport/devices', [
            'organization_id' => $organization->id,
            'device_code' => 'IOT-WEB-001',
            'serial_number' => 'SERIAL-WEB-001',
            'display_name' => 'Web Provisioned Sensor',
            'firmware_version' => '1.0.0',
            'supports_product_temperature' => '1',
            'supports_air_temperature' => '1',
            'supports_humidity' => '1',
            'supports_gps' => '1',
            'supports_door_sensor' => '0',
            'provision_now' => '1',
        ]);

        $response->assertOk()
            ->assertSee('One-time credentials')
            ->assertSee('device+iot-web-001@fishtrace.invalid');
        $this->assertDatabaseHas('iot_devices', [
            'device_code' => 'IOT-WEB-001',
            'firebase_auth_enabled' => true,
            'credential_version' => 1,
            'supports_door_sensor' => false,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'IOT_DEVICE_CREATED']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'IOT_DEVICE_PROVISIONED']);
    }

    public function test_transporter_cannot_open_or_submit_device_registration(): void
    {
        $this->seed();
        $transporter = User::query()->where('email', 'transporter@fishtrace.demo')->firstOrFail();

        $this->actingAs($transporter)->get('/admin/transport/devices/create')->assertForbidden();
        $this->actingAs($transporter)->post('/admin/transport/devices', [])->assertForbidden();
    }
}
