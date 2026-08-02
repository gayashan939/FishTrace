<?php

namespace App\Actions\Transport;

use App\Enums\TransportTripStatus;
use App\Models\FishBatch;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class AddBatchToTrip
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $user, TransportTrip $trip, string $batchId): TransportTrip
    {
        $batch = DB::transaction(function () use ($trip, $batchId): FishBatch {
            $lockedTrip = TransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($lockedTrip->hasStatus(TransportTripStatus::DRAFT) || $lockedTrip->hasStatus(TransportTripStatus::READY), 409, 'Batches can only be changed before departure.');
            $batch = FishBatch::query()->lockForUpdate()->findOrFail($batchId);
            abort_if($batch->is_recalled, 409, 'A recalled batch cannot be transported.');
            abort_if($batch->transportTrips()->whereIn('transport_trips.status', ['DRAFT', 'READY', 'ACTIVE'])->where('transport_trips.id', '!=', $lockedTrip->id)->exists(), 409, 'The batch is already assigned to another unfinished trip.');
            $lockedTrip->batches()->syncWithoutDetaching([$batch->id]);

            return $batch;
        });
        $this->audit->record('TRANSPORT_BATCH_ASSIGNED', $trip, null, ['batch_id' => $batch->id], $user, $trip->organization_id);

        return $trip->fresh('batches');
    }
}
