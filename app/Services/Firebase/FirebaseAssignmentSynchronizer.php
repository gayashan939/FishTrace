<?php

namespace App\Services\Firebase;

use App\Contracts\Firebase\FirebaseRealtimeClient;
use App\Enums\NotificationType;
use App\Models\DeviceAssignment;
use App\Models\IotDevice;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OperationalNotifier;
use Illuminate\Support\Carbon;
use Throwable;

class FirebaseAssignmentSynchronizer
{
    public function __construct(private FirebaseRealtimeClient $firebase, private OperationalNotifier $notifier, private AuditLogger $audit) {}

    public function attempt(DeviceAssignment $assignment): bool
    {
        try {
            $assignment->status === 'ACTIVE' ? $this->mirrorActive($assignment) : $this->mirrorEnded($assignment);
            $assignment->update(['firebase_sync_status' => 'SYNCED', 'firebase_sync_error' => null]);

            return true;
        } catch (Throwable $exception) {
            report($exception);
            $message = 'Firebase assignment synchronization failed ('.class_basename($exception).').';
            $assignment->update(['firebase_sync_status' => 'FAILED', 'firebase_sync_error' => $message]);
            $trip = TransportTrip::findOrFail($assignment->transport_trip_id);
            $this->audit->record('FIREBASE_ASSIGNMENT_FAILED', $assignment, null, ['firebase_sync_status' => 'FAILED', 'error_message' => $message], organizationId: $trip->organization_id);
            $this->notifier->organizationOnce('firebase-assignment-failure:'.$assignment->id, $trip->organization_id, NotificationType::FIREBASE_ASSIGNMENT_FAILURE, 'Firebase assignment synchronization failed', 'A device assignment could not be synchronized and will be retried.', ['transport_trip_id' => $trip->id, 'device_assignment_id' => $assignment->id]);

            return false;
        }
    }

    private function mirrorActive(DeviceAssignment $assignment): void
    {
        $trip = TransportTrip::with('batches:id,organization_id')->findOrFail($assignment->transport_trip_id);
        $device = IotDevice::findOrFail($assignment->iot_device_id);
        abort_unless($device->firebase_uid !== null && $device->firebase_auth_enabled, 409, 'The device is not provisioned for Firebase.');
        $batchIds = $trip->batches->pluck('id')->all();
        $organizationIds = $trip->batches->pluck('organization_id')->push($trip->organization_id)->unique()->values();
        $members = User::query()->whereHas('organizations', fn ($query) => $query->whereIn('organizations.id', $organizationIds))->whereNotNull('firebase_uid')->pluck('firebase_uid')->all();

        $this->firebase->set('deviceAssignments/'.$device->firebase_uid, ['deviceCode' => $device->device_code, 'deviceId' => $device->id, 'tripId' => $trip->id, 'batchIds' => array_fill_keys($batchIds, true), 'active' => true, 'assignedAt' => Carbon::parse($assignment->assigned_at)->getTimestampMs(), 'expiresAt' => $assignment->expires_at === null ? null : Carbon::parse($assignment->expires_at)->getTimestampMs()]);
        $this->firebase->set('tripMembers/'.$trip->id, array_fill_keys($members, true));
    }

    private function mirrorEnded(DeviceAssignment $assignment): void
    {
        $trip = TransportTrip::findOrFail($assignment->transport_trip_id);
        $device = IotDevice::findOrFail($assignment->iot_device_id);
        if ($device->firebase_uid !== null) {
            $this->firebase->set('deviceAssignments/'.$device->firebase_uid, ['deviceCode' => $device->device_code, 'deviceId' => $device->id, 'tripId' => $trip->id, 'batchIds' => (object) [], 'active' => false, 'endedAt' => ($assignment->ended_at ?? now())->getTimestampMs()]);
        }
        $this->firebase->remove('liveTrips/'.$trip->id);
        $this->firebase->remove('tripMembers/'.$trip->id);
    }
}
