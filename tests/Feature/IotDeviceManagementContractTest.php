<?php

namespace Tests\Feature;

use App\Contracts\Firebase\FirebaseDeviceAuth;
use App\Models\IotDevice;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class IotDeviceManagementContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_responses_hide_firebase_identity_and_assignment_failure_details(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($admin);
        $device = IotDevice::query()->whereHas('assignments')->firstOrFail();
        $device->forceFill([
            'firebase_uid' => 'private-firebase-uid',
            'firebase_email' => 'private-device@firebase.invalid',
        ])->save();
        $device->assignments()->firstOrFail()->forceFill([
            'firebase_sync_error' => 'private-provider-failure-detail',
        ])->save();

        $this->getJson("/api/v1/iot/devices/{$device->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.firebase_uid')
            ->assertJsonMissingPath('data.firebase_email')
            ->assertJsonMissingPath('data.assignments.0.firebase_sync_error')
            ->assertDontSee('private-firebase-uid')
            ->assertDontSee('private-device@firebase.invalid')
            ->assertDontSee('private-provider-failure-detail');

        $this->getJson('/api/v1/iot/devices')
            ->assertOk()
            ->assertDontSee('private-firebase-uid')
            ->assertDontSee('firebase_email');
    }

    public function test_device_crud_is_audited_and_credentials_follow_explicit_states(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $organization = Organization::query()->where('type', 'TRANSPORTER')->firstOrFail();
        Sanctum::actingAs($admin);

        $device = $this->postJson('/api/v1/iot/devices', [
            'organization_id' => $organization->id,
            'device_code' => 'IOT-CONTRACT-01',
            'serial_number' => 'SERIAL-CONTRACT-01',
            'display_name' => 'Contract Reefer Sensor',
        ])->assertCreated()->json('data');

        $this->assertDatabaseHas('audit_logs', ['action' => 'IOT_DEVICE_CREATED', 'auditable_id' => $device['id'], 'user_id' => $admin->id]);
        $this->postJson("/api/v1/iot/devices/{$device['id']}/firebase-rotate")->assertConflict();
        $this->postJson("/api/v1/iot/devices/{$device['id']}/firebase-provision")
            ->assertOk()
            ->assertJsonPath('data.credential_version', 1);
        $this->postJson("/api/v1/iot/devices/{$device['id']}/firebase-provision")->assertConflict();

        $this->patchJson("/api/v1/iot/devices/{$device['id']}", ['display_name' => 'Updated Contract Sensor'])
            ->assertOk()
            ->assertJsonPath('data.display_name', 'Updated Contract Sensor');
        $this->assertDatabaseHas('audit_logs', ['action' => 'IOT_DEVICE_UPDATED', 'auditable_id' => $device['id'], 'user_id' => $admin->id]);
    }

    public function test_firebase_provisioning_failure_does_not_commit_credential_state(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $organization = Organization::query()->where('type', 'TRANSPORTER')->firstOrFail();
        Sanctum::actingAs($admin);
        $device = $this->postJson('/api/v1/iot/devices', [
            'organization_id' => $organization->id,
            'device_code' => 'IOT-FAILURE-01',
            'serial_number' => 'SERIAL-FAILURE-01',
            'display_name' => 'Failure Sensor',
        ])->assertCreated()->json('data');

        $auth = Mockery::mock(FirebaseDeviceAuth::class);
        $auth->shouldReceive('upsert')->once()->andThrow(new RuntimeException('provider unavailable'));
        app()->instance(FirebaseDeviceAuth::class, $auth);

        $this->postJson("/api/v1/iot/devices/{$device['id']}/firebase-provision")->assertServerError();

        $this->assertDatabaseHas('iot_devices', [
            'id' => $device['id'],
            'firebase_auth_enabled' => false,
            'credential_version' => 0,
        ]);
    }
}
