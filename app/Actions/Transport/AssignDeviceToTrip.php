<?php

namespace App\Actions\Transport;

use App\Enums\TransportTripStatus;
use App\Models\DeviceAssignment;
use App\Models\IotDevice;
use App\Models\TransportTrip;
use App\Services\Firebase\FirebaseAssignmentSynchronizer;
use Illuminate\Support\Facades\DB;

class AssignDeviceToTrip
{
    public function __construct(private FirebaseAssignmentSynchronizer $synchronizer) {}

    public function execute(TransportTrip $trip, string $deviceId, ?string $expiresAt): DeviceAssignment
    {
        [$assignment, $endedAssignments] = DB::transaction(function () use ($trip, $deviceId, $expiresAt): array {
            $lockedTrip = TransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($lockedTrip->hasStatus(TransportTripStatus::DRAFT) || $lockedTrip->hasStatus(TransportTripStatus::READY), 409, 'A device can only be assigned before departure.');
            $device = IotDevice::query()->where('organization_id', $lockedTrip->organization_id)->where('status', 'ACTIVE')->lockForUpdate()->findOrFail($deviceId);
            abort_unless($device->firebase_auth_enabled && $device->firebase_uid, 409, 'The device must be provisioned in Firebase.');
            abort_if(DeviceAssignment::query()->where('iot_device_id', $device->id)->where('status', 'ACTIVE')->where('transport_trip_id', '!=', $lockedTrip->id)->exists(), 409, 'The device is already assigned to an active trip.');
            $endedAssignments = DeviceAssignment::query()->where('transport_trip_id', $lockedTrip->id)->where('status', 'ACTIVE')->lockForUpdate()->get();
            foreach ($endedAssignments as $endedAssignment) {
                $endedAssignment->update(['status' => 'ENDED', 'ended_at' => now(), 'firebase_sync_status' => 'PENDING']);
            }

            return [DeviceAssignment::create(['iot_device_id' => $device->id, 'transport_trip_id' => $lockedTrip->id, 'status' => 'ACTIVE', 'firebase_sync_status' => 'PENDING', 'assigned_at' => now(), 'expires_at' => $expiresAt]), $endedAssignments];
        });
        foreach ($endedAssignments as $endedAssignment) {
            $this->synchronizer->attempt($endedAssignment);
        }
        $this->synchronizer->attempt($assignment);

        return $assignment->fresh(['device']);
    }
}
