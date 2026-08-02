<?php

namespace App\Actions\Transport;

use App\Enums\TransportTripStatus;
use App\Models\DeviceAssignment;
use App\Models\TransportTrip;
use App\Services\Firebase\FirebaseAssignmentSynchronizer;
use Illuminate\Support\Facades\DB;

class RemoveDeviceFromTrip
{
    public function __construct(private FirebaseAssignmentSynchronizer $synchronizer) {}

    public function execute(TransportTrip $trip): DeviceAssignment
    {
        $assignment = DB::transaction(function () use ($trip): DeviceAssignment {
            $lockedTrip = TransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($lockedTrip->hasStatus(TransportTripStatus::DRAFT) || $lockedTrip->hasStatus(TransportTripStatus::READY), 409, 'A device can only be removed before departure.');
            $assignment = DeviceAssignment::query()->where('transport_trip_id', $lockedTrip->id)->where('status', 'ACTIVE')->lockForUpdate()->firstOrFail();
            $assignment->update(['status' => 'ENDED', 'ended_at' => now(), 'firebase_sync_status' => 'PENDING']);

            return $assignment;
        });
        $this->synchronizer->attempt($assignment);

        return $assignment->fresh('device');
    }
}
