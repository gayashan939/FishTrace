<?php

namespace App\Actions\Transport;

use App\Enums\TransportTripStatus;
use App\Models\FishBatch;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class MarkTransportTripArrived
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $user, TransportTrip $trip): TransportTrip
    {
        $arrived = DB::transaction(function () use ($user, $trip): TransportTrip {
            $locked = TransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($locked->hasStatus(TransportTripStatus::ACTIVE), 409, 'Only an active transport trip can be marked as arrived.');

            if ($locked->arrived_at === null) {
                $locked->update(['arrived_at' => now()]);
                foreach ($locked->batches()->pluck('fish_batches.id') as $batchId) {
                    FishBatch::findOrFail($batchId)->events()->create([
                        'organization_id' => $locked->organization_id,
                        'actor_id' => $user->id,
                        'event_type' => 'TRANSPORT_ARRIVED',
                        'title' => 'Transport arrived at destination',
                        'public_data' => ['trip_code' => $locked->trip_code, 'destination' => $locked->destination],
                        'occurred_at' => $locked->arrived_at,
                    ]);
                }
            }

            return $locked;
        });

        $this->audit->record('TRANSPORT_TRIP_ARRIVED', $arrived, null, ['arrived_at' => $arrived->arrived_at], $user, $arrived->organization_id);

        return $arrived->fresh(['vehicle', 'batches', 'activeAssignment.device', 'checklist.items', 'deliveryConfirmation']);
    }
}
