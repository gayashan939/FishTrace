<?php

namespace App\Actions\Transport;

use App\Enums\NotificationType;
use App\Enums\TransportTripStatus;
use App\Models\DeviceAssignment;
use App\Models\FishBatch;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\Firebase\FirebaseAssignmentSynchronizer;
use App\Services\Notifications\OperationalNotifier;
use Illuminate\Support\Facades\DB;

class CompleteTransportTrip
{
    public function __construct(private FirebaseAssignmentSynchronizer $synchronizer, private OperationalNotifier $notifier) {}

    public function execute(User $user, TransportTrip $trip): TransportTrip
    {
        [$completedTrip, $assignment] = DB::transaction(function () use ($user, $trip): array {
            $locked = TransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            if ($locked->hasStatus(TransportTripStatus::COMPLETED)) {
                return [$locked, null];
            }
            abort_unless($locked->hasStatus(TransportTripStatus::ACTIVE), 409, 'Only an active transport trip can be completed.');
            abort_unless($locked->deliveryConfirmation()->exists(), 409, 'Record receiver delivery confirmation before completing the trip.');
            $assignment = DeviceAssignment::query()->where('transport_trip_id', $locked->id)->where('status', 'ACTIVE')->lockForUpdate()->first();
            $locked->update(['status' => TransportTripStatus::COMPLETED, 'completed_at' => now()]);
            if ($assignment !== null) {
                $assignment->update(['status' => 'ENDED', 'ended_at' => now(), 'firebase_sync_status' => 'PENDING']);
            }
            foreach ($locked->batches()->pluck('fish_batches.id') as $batchId) {
                $batch = FishBatch::findOrFail($batchId);
                $batch->events()->create(['organization_id' => $locked->organization_id, 'actor_id' => $user->id, 'event_type' => 'TRANSPORT_COMPLETED', 'title' => 'Cold-chain delivery completed', 'public_data' => ['trip_code' => $locked->trip_code, 'destination' => $locked->destination], 'occurred_at' => now()]);
            }
            $this->notifier->organization($locked->organization_id, NotificationType::DELIVERY_COMPLETED, 'Delivery completed', $locked->trip_code.' completed delivery to '.$locked->destination.'.', ['transport_trip_id' => $locked->id]);

            return [$locked, $assignment];
        });

        if ($assignment !== null) {
            $this->synchronizer->attempt($assignment);
        }

        return $completedTrip->fresh(['vehicle', 'batches', 'activeAssignment.device']);
    }
}
