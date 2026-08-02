<?php

namespace App\Actions\Transport;

use App\Enums\TransportTripStatus;
use App\Models\FishBatch;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class RemoveBatchFromTrip
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $user, TransportTrip $trip, FishBatch $batch): TransportTrip
    {
        DB::transaction(function () use ($trip, $batch): void {
            $lockedTrip = TransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($lockedTrip->hasStatus(TransportTripStatus::DRAFT) || $lockedTrip->hasStatus(TransportTripStatus::READY), 409, 'Batches can only be changed before departure.');
            FishBatch::query()->lockForUpdate()->findOrFail($batch->id);
            abort_unless($lockedTrip->batches()->whereKey($batch->id)->exists(), 404, 'The batch is not assigned to this trip.');
            $lockedTrip->batches()->detach($batch->id);
        });
        $this->audit->record('TRANSPORT_BATCH_REMOVED', $trip, ['batch_id' => $batch->id], null, $user, $trip->organization_id);

        return $trip->fresh('batches');
    }
}
