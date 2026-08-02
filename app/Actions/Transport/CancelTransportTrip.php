<?php

namespace App\Actions\Transport;

use App\Enums\TransportTripStatus;
use App\Models\DeviceAssignment;
use App\Models\FishBatch;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Firebase\FirebaseAssignmentSynchronizer;
use Illuminate\Support\Facades\DB;

class CancelTransportTrip
{
    public function __construct(private FirebaseAssignmentSynchronizer $synchronizer, private AuditLogger $audit) {}

    public function execute(User $user, TransportTrip $trip, string $reason): TransportTrip
    {
        [$trip, $assignment] = DB::transaction(function () use ($trip, $user, $reason): array {
            $locked = TransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($locked->hasStatus(TransportTripStatus::DRAFT) || $locked->hasStatus(TransportTripStatus::READY), 409, 'Only a trip that has not departed can be cancelled.');
            $assignment = DeviceAssignment::query()->where('transport_trip_id', $locked->id)->where('status', 'ACTIVE')->lockForUpdate()->first();
            if ($assignment !== null) {
                $assignment->update(['status' => 'ENDED', 'ended_at' => now(), 'firebase_sync_status' => 'PENDING']);
            }
            $locked->update(['status' => TransportTripStatus::CANCELLED]);
            foreach ($locked->batches()->pluck('fish_batches.id') as $batchId) {
                FishBatch::findOrFail($batchId)->events()->create(['organization_id' => $locked->organization_id, 'actor_id' => $user->id, 'event_type' => 'TRANSPORT_CANCELLED', 'title' => 'Planned transport cancelled', 'public_data' => ['trip_code' => $locked->trip_code], 'private_data' => ['reason' => $reason], 'occurred_at' => now()]);
            }

            return [$locked, $assignment];
        });
        if ($assignment !== null) {
            $this->synchronizer->attempt($assignment);
        }
        $this->audit->record('TRANSPORT_TRIP_CANCELLED', $trip, null, ['status' => 'CANCELLED', 'reason' => $reason], $user, $trip->organization_id);

        return $trip->fresh(['vehicle', 'batches', 'checklist.items']);
    }
}
