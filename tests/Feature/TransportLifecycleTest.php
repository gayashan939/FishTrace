<?php

namespace Tests\Feature;

use App\Contracts\Firebase\FirebaseRealtimeClient;
use App\Models\DeviceAssignment;
use App\Models\FishBatch;
use App\Models\IotDevice;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\Firebase\MockFirebaseClient;
use App\Services\Transport\TransportChecklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class TransportLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_transport_directories_are_bounded_and_nested_resources_hide_firebase_diagnostics(): void
    {
        $this->seed();
        $transporter = $this->transporter();
        Sanctum::actingAs($transporter);

        foreach (['vehicles', 'transport-trips'] as $directory) {
            $this->getJson("/api/v1/{$directory}?per_page=101")
                ->assertUnprocessable()
                ->assertJsonPath('error.code', 'VALIDATION_FAILED')
                ->assertJsonStructure(['error' => ['field_errors' => ['per_page']]]);
        }

        $trip = TransportTrip::query()
            ->where('organization_id', $transporter->primaryOrganization()?->id)
            ->whereHas('activeAssignment')
            ->firstOrFail();
        $assignment = $trip->activeAssignment()->firstOrFail();
        $assignment->forceFill(['firebase_sync_error' => 'private-assignment-provider-detail'])->save();
        $assignment->device()->firstOrFail()->forceFill([
            'firebase_uid' => 'private-transport-device-uid',
            'firebase_email' => 'private-transport-device@firebase.invalid',
        ])->save();

        $this->getJson("/api/v1/transport-trips/{$trip->id}")
            ->assertOk()
            ->assertJsonMissingPath('data.active_assignment.firebase_sync_error')
            ->assertJsonMissingPath('data.active_assignment.device.firebase_uid')
            ->assertJsonMissingPath('data.active_assignment.device.firebase_email')
            ->assertDontSee('private-assignment-provider-detail')
            ->assertDontSee('private-transport-device-uid')
            ->assertDontSee('private-transport-device@firebase.invalid');

        $this->getJson('/api/v1/transport-trips')
            ->assertOk()
            ->assertDontSee('private-assignment-provider-detail')
            ->assertDontSee('private-transport-device-uid');
    }

    public function test_vehicle_crud_is_scoped_and_protects_unfinished_trips(): void
    {
        $this->seed();
        $transporter = $this->transporter();
        Sanctum::actingAs($transporter);
        $vehicle = $this->postJson('/api/v1/vehicles', ['registration_number' => 'WP-TEST-1001', 'name' => 'Test Reefer'])->assertCreated()->json('data');
        $this->patchJson("/api/v1/vehicles/{$vehicle['id']}", ['name' => 'Updated Reefer'])->assertOk()->assertJsonPath('data.name', 'Updated Reefer');
        $trip = $this->postJson('/api/v1/transport-trips', ['vehicle_id' => $vehicle['id'], 'driver_name' => 'Test Driver', 'origin' => 'Galle', 'destination' => 'Colombo'])->assertCreated()->json('data');

        $this->patchJson("/api/v1/vehicles/{$vehicle['id']}", ['is_active' => false])->assertConflict();
        $this->deleteJson("/api/v1/vehicles/{$vehicle['id']}")->assertConflict();

        Sanctum::actingAs(User::where('email', 'retailer@fishtrace.demo')->firstOrFail());
        $this->getJson("/api/v1/vehicles/{$vehicle['id']}")->assertForbidden();
        $this->getJson("/api/v1/transport-trips/{$trip['id']}")->assertForbidden();
    }

    public function test_device_credentials_are_returned_once_rotated_and_disabled_without_storage(): void
    {
        $this->seed();
        [$device, $firstPassword] = $this->createProvisionedDevice();
        $admin = User::where('email', 'admin@fishtrace.demo')->firstOrFail();

        $rotated = $this->postJson("/api/v1/iot/devices/{$device->id}/firebase-rotate")->assertOk()->assertJsonPath('data.credential_version', 2)->json('data');
        $this->assertNotSame($firstPassword, $rotated['firebase_password']);
        $this->assertArrayNotHasKey('password', Cache::get('fishtrace.firebase.mock.users')[$device->firebase_uid]);
        $this->getJson("/api/v1/iot/devices/{$device->id}")->assertOk()->assertDontSee('firebase_password')->assertDontSee($firstPassword);
        $this->postJson("/api/v1/iot/devices/{$device->id}/firebase-disable")->assertOk()->assertJsonPath('data.firebase_auth_enabled', false);
        $this->postJson("/api/v1/iot/devices/{$device->id}/deactivate")->assertOk()->assertJsonPath('data.status', 'INACTIVE');
        $this->postJson("/api/v1/iot/devices/{$device->id}/activate")->assertOk()->assertJsonPath('data.status', 'ACTIVE')->assertJsonPath('data.firebase_auth_enabled', false);
        $this->assertDatabaseHas('audit_logs', ['action' => 'IOT_DEVICE_CREDENTIAL_ROTATED', 'user_id' => $admin->id]);
    }

    public function test_checklist_assignment_incident_delivery_and_completion_form_a_gated_workflow(): void
    {
        $this->seed();
        [$device] = $this->createProvisionedDevice();
        $transporter = $this->transporter();
        Sanctum::actingAs($transporter);
        [$trip, $batch] = $this->draftTripWithBatch();

        $assignment = $this->postJson("/api/v1/transport-trips/{$trip->id}/device", ['iot_device_id' => $device->id])->assertOk()->assertJsonPath('data.firebase_sync_status', 'SYNCED')->json('data');
        $firebase = app(FirebaseRealtimeClient::class);
        $members = $firebase->get('tripMembers/'.$trip->id);
        $this->assertArrayHasKey((string) $transporter->firebase_uid, $members);
        $this->assertArrayHasKey((string) User::where('email', 'processor@fishtrace.demo')->value('firebase_uid'), $members);

        $this->postJson("/api/v1/transport-trips/{$trip->id}/start")->assertConflict();
        $partial = collect(TransportChecklistService::ITEMS)->keys()->map(fn (string $key): array => ['key' => $key, 'completed' => $key !== 'device_online'])->all();
        $this->postJson("/api/v1/transport-trips/{$trip->id}/checklist", ['items' => $partial])->assertOk();
        $this->assertDatabaseHas('transport_trips', ['id' => $trip->id, 'status' => 'DRAFT']);
        $complete = collect(TransportChecklistService::ITEMS)->keys()->map(fn (string $key): array => ['key' => $key, 'completed' => true])->all();
        $this->postJson("/api/v1/transport-trips/{$trip->id}/checklist", ['items' => $complete])->assertOk();
        $this->postJson("/api/v1/transport-trips/{$trip->id}/start")->assertOk()->assertJsonPath('data.status', 'ACTIVE');

        Sanctum::actingAs(User::where('email', 'admin@fishtrace.demo')->firstOrFail());
        $this->postJson("/api/v1/iot/devices/{$device->id}/firebase-rotate")->assertConflict();
        Sanctum::actingAs($transporter);
        $this->postJson("/api/v1/transport-trips/{$trip->id}/incidents", ['type' => 'TRAFFIC_DELAY', 'severity' => 'WARNING', 'description' => 'Road closure delayed the refrigerated vehicle.', 'occurred_at' => now()->toIso8601String()])->assertCreated();
        $this->postJson("/api/v1/transport-trips/{$trip->id}/complete")->assertConflict();
        $delivery = ['receiver_name' => 'Receiving Officer', 'receiver_contact' => '+94 11 555 0100', 'delivered_at' => now()->toIso8601String()];
        $this->postJson("/api/v1/transport-trips/{$trip->id}/delivery-confirmation", $delivery)->assertOk();
        $this->postJson("/api/v1/transport-trips/{$trip->id}/delivery-confirmation", $delivery)->assertOk();
        $this->assertDatabaseCount('delivery_confirmations', 1);
        $this->postJson("/api/v1/transport-trips/{$trip->id}/complete")->assertOk()->assertJsonPath('data.status', 'COMPLETED');

        $this->assertDatabaseHas('device_assignments', ['id' => $assignment['id'], 'status' => 'ENDED', 'firebase_sync_status' => 'SYNCED']);
        $this->assertFalse($firebase->get('deviceAssignments/'.$device->firebase_uid)['active']);
        $this->assertSame([], $firebase->get('tripMembers/'.$trip->id));
        $this->assertSame([], $firebase->get('liveTrips/'.$trip->id));
        $this->assertDatabaseHas('traceability_events', ['fish_batch_id' => $batch->id, 'event_type' => 'TRANSPORT_INCIDENT']);
        $this->assertDatabaseHas('traceability_events', ['fish_batch_id' => $batch->id, 'event_type' => 'DELIVERY_CONFIRMED']);
        $this->assertDatabaseHas('traceability_events', ['fish_batch_id' => $batch->id, 'event_type' => 'TRANSPORT_COMPLETED']);
    }

    public function test_draft_assignment_can_be_removed_and_device_can_then_be_deactivated(): void
    {
        $this->seed();
        [$device] = $this->createProvisionedDevice();
        Sanctum::actingAs($this->transporter());
        [$trip] = $this->draftTripWithBatch();
        $this->postJson("/api/v1/transport-trips/{$trip->id}/device", ['iot_device_id' => $device->id])->assertOk();
        $this->deleteJson("/api/v1/transport-trips/{$trip->id}/device")->assertOk()->assertJsonPath('data.status', 'ENDED');

        Sanctum::actingAs(User::where('email', 'admin@fishtrace.demo')->firstOrFail());
        $this->postJson("/api/v1/iot/devices/{$device->id}/deactivate")->assertOk()->assertJsonPath('data.status', 'INACTIVE');
    }

    public function test_failed_firebase_assignment_is_safely_recorded_and_reconciled(): void
    {
        $this->seed();
        [$device] = $this->createProvisionedDevice();
        Sanctum::actingAs($this->transporter());
        [$trip] = $this->draftTripWithBatch();
        $this->app->instance(FirebaseRealtimeClient::class, new class implements FirebaseRealtimeClient
        {
            public function set(string $path, array $value): void
            {
                throw new RuntimeException('secret provider failure');
            }

            public function remove(string $path): void
            {
                throw new RuntimeException('secret provider failure');
            }

            public function get(string $path): array
            {
                return [];
            }

            public function markSynchronized(string $deviceUid, string $messageId, array $metadata): void
            {
                throw new RuntimeException('secret provider failure');
            }
        });

        $assignment = $this->postJson("/api/v1/transport-trips/{$trip->id}/device", ['iot_device_id' => $device->id])->assertOk()->assertJsonPath('data.firebase_sync_status', 'FAILED')->json('data');
        $this->assertStringNotContainsString('secret provider failure', (string) DeviceAssignment::findOrFail($assignment['id'])->firebase_sync_error);
        $this->postJson("/api/v1/transport-trips/{$trip->id}/start")->assertConflict();
        $this->artisan('fishtrace:reconcile-firebase-assignments')->assertFailed();

        $this->app->instance(FirebaseRealtimeClient::class, new MockFirebaseClient);
        $this->artisan('fishtrace:reconcile-firebase-assignments')->assertSuccessful();
        $this->assertDatabaseHas('device_assignments', ['id' => $assignment['id'], 'firebase_sync_status' => 'SYNCED', 'firebase_sync_error' => null]);
    }

    public function test_trip_edit_batch_removal_dashboard_summary_and_cancellation_are_functional(): void
    {
        $this->seed();
        [$device] = $this->createProvisionedDevice();
        Sanctum::actingAs($this->transporter());
        [$trip, $batch] = $this->draftTripWithBatch();
        $this->patchJson("/api/v1/transport-trips/{$trip->id}", ['destination' => 'Negombo'])->assertOk()->assertJsonPath('data.destination', 'Negombo');
        $this->deleteJson("/api/v1/transport-trips/{$trip->id}/batches/{$batch->id}")->assertOk()->assertJsonCount(0, 'data.batches');
        $this->postJson("/api/v1/transport-trips/{$trip->id}/batches", ['batch_id' => $batch->id])->assertOk();
        $this->postJson("/api/v1/transport-trips/{$trip->id}/device", ['iot_device_id' => $device->id])->assertOk();

        $this->getJson('/api/v1/transporter/dashboard')->assertOk()->assertJsonStructure(['data' => ['trip_counts', 'active_assignments', 'open_alerts', 'offline_devices', 'recent_trips']]);
        $seededTrip = TransportTrip::where('status', 'ACTIVE')->firstOrFail();
        $this->getJson("/api/v1/transport-trips/{$seededTrip->id}/sensor-summary")->assertOk()->assertJsonPath('data.reading_count', 12);
        $this->postJson("/api/v1/transport-trips/{$trip->id}/cancel", ['reason' => 'Customer requested a revised collection schedule.'])->assertOk()->assertJsonPath('data.status', 'CANCELLED');

        $this->assertDatabaseHas('device_assignments', ['transport_trip_id' => $trip->id, 'status' => 'ENDED', 'firebase_sync_status' => 'SYNCED']);
        $this->assertDatabaseHas('traceability_events', ['fish_batch_id' => $batch->id, 'event_type' => 'TRANSPORT_CANCELLED']);
        $this->postJson("/api/v1/transport-trips/{$trip->id}/start")->assertConflict();
    }

    public function test_departed_trip_rejects_predeparture_mutations_under_locked_state_checks(): void
    {
        $this->seed();
        $transporter = $this->transporter();
        Sanctum::actingAs($transporter);
        $trip = TransportTrip::query()->where('organization_id', $transporter->primaryOrganization()?->id)->where('status', 'ACTIVE')->firstOrFail();
        $device = IotDevice::query()->create([
            'organization_id' => $transporter->primaryOrganization()?->id,
            'device_code' => 'IOT-LATE-ASSIGN',
            'serial_number' => 'SERIAL-LATE-ASSIGN',
            'display_name' => 'Late Assignment Sensor',
            'status' => 'ACTIVE',
            'firebase_uid' => 'device:late-assignment',
            'firebase_auth_enabled' => true,
        ]);
        $items = collect(TransportChecklistService::ITEMS)->keys()->map(fn (string $key): array => ['key' => $key, 'completed' => true])->all();

        $this->postJson("/api/v1/transport-trips/{$trip->id}/device", ['iot_device_id' => $device->id])->assertConflict();
        $this->deleteJson("/api/v1/transport-trips/{$trip->id}/device")->assertConflict();
        $this->postJson("/api/v1/transport-trips/{$trip->id}/checklist", ['items' => $items])->assertConflict();
        $this->patchJson("/api/v1/transport-trips/{$trip->id}", ['destination' => 'Changed after departure'])->assertConflict();
        $this->assertDatabaseMissing('device_assignments', ['iot_device_id' => $device->id]);
    }

    public function test_completed_trip_rejects_late_incident_and_delivery_confirmation(): void
    {
        $this->seed();
        $transporter = $this->transporter();
        Sanctum::actingAs($transporter);
        $trip = TransportTrip::query()->where('organization_id', $transporter->primaryOrganization()?->id)->where('status', 'ACTIVE')->firstOrFail();
        $trip->update(['status' => 'COMPLETED', 'completed_at' => now()]);
        $incidentCount = $trip->incidents()->count();

        $this->postJson("/api/v1/transport-trips/{$trip->id}/incidents", ['type' => 'LATE_ENTRY', 'severity' => 'INFO', 'description' => 'This incident is too late for the completed trip.', 'occurred_at' => now()->toIso8601String()])->assertConflict();
        $this->postJson("/api/v1/transport-trips/{$trip->id}/delivery-confirmation", ['receiver_name' => 'Late Receiver', 'delivered_at' => now()->toIso8601String()])->assertConflict();

        $this->assertSame($incidentCount, $trip->incidents()->count());
        $this->assertDatabaseMissing('delivery_confirmations', ['transport_trip_id' => $trip->id]);
    }

    public function test_batch_removal_requires_membership_in_the_parent_trip(): void
    {
        $this->seed();
        Sanctum::actingAs($this->transporter());
        [$trip] = $this->draftTripWithBatch();
        $foreignBatch = TransportTrip::query()->where('id', '!=', $trip->id)->whereHas('batches')->firstOrFail()->batches()->firstOrFail();

        $this->deleteJson("/api/v1/transport-trips/{$trip->id}/batches/{$foreignBatch->id}")->assertNotFound();
        $this->assertDatabaseHas('transport_batches', ['transport_trip_id' => $trip->id]);
        $this->assertDatabaseHas('transport_batches', ['fish_batch_id' => $foreignBatch->id]);
    }

    /** @return array{IotDevice, string} */
    private function createProvisionedDevice(): array
    {
        $admin = User::where('email', 'admin@fishtrace.demo')->firstOrFail();
        $organizationId = $this->transporter()->primaryOrganization()?->id;
        Sanctum::actingAs($admin);
        $device = $this->postJson('/api/v1/iot/devices', ['organization_id' => $organizationId, 'device_code' => 'IOT-TEST-'.IotDevice::count(), 'serial_number' => 'SERIAL-TEST-'.IotDevice::count(), 'display_name' => 'Test Reefer Sensor'])->assertCreated()->json('data');
        $credential = $this->postJson("/api/v1/iot/devices/{$device['id']}/firebase-provision")->assertOk()->assertJsonPath('data.credential_version', 1)->json('data');

        return [IotDevice::findOrFail($device['id']), $credential['firebase_password']];
    }

    /** @return array{TransportTrip, FishBatch} */
    private function draftTripWithBatch(): array
    {
        $vehicle = $this->postJson('/api/v1/vehicles', ['registration_number' => 'WP-TRIP-'.TransportTrip::count(), 'name' => 'Lifecycle Reefer'])->assertCreated()->json('data');
        $tripData = $this->postJson('/api/v1/transport-trips', ['vehicle_id' => $vehicle['id'], 'driver_name' => 'Lifecycle Driver', 'origin' => 'Matara', 'destination' => 'Colombo'])->assertCreated()->json('data');
        $batch = FishBatch::query()->whereDoesntHave('transportTrips')->firstOrFail();
        $this->postJson("/api/v1/transport-trips/{$tripData['id']}/batches", ['batch_id' => $batch->id])->assertOk();

        return [TransportTrip::findOrFail($tripData['id']), $batch];
    }

    private function transporter(): User
    {
        return User::where('email', 'transporter@fishtrace.demo')->firstOrFail();
    }
}
