<?php

namespace App\Actions\Transport;

use App\Enums\TransportTripStatus;
use App\Models\DeliveryConfirmation;
use App\Models\FishBatch;
use App\Models\TraceabilityEvent;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class ConfirmTransportDelivery
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $user, TransportTrip $trip, array $data): DeliveryConfirmation
    {
        $confirmation = DB::transaction(function () use ($user, $trip, $data): DeliveryConfirmation {
            $lockedTrip = TransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($lockedTrip->hasStatus(TransportTripStatus::ACTIVE), 409, 'Delivery can only be confirmed for an active trip.');
            $confirmation = DeliveryConfirmation::query()->updateOrCreate(
                ['transport_trip_id' => $lockedTrip->id],
                $data + ['confirmed_by' => $user->id],
            );
            foreach ($lockedTrip->batches()->pluck('fish_batches.id') as $batchId) {
                $event = TraceabilityEvent::query()->where('fish_batch_id', $batchId)->where('event_type', 'DELIVERY_CONFIRMED')->where('private_data->transport_trip_id', $lockedTrip->id)->first();
                $values = ['organization_id' => $lockedTrip->organization_id, 'actor_id' => $user->id, 'title' => 'Delivery received', 'public_data' => ['destination' => $lockedTrip->destination, 'delivered_at' => $confirmation->delivered_at], 'private_data' => ['transport_trip_id' => $lockedTrip->id], 'occurred_at' => $confirmation->delivered_at];
                if ($event === null) {
                    FishBatch::findOrFail($batchId)->events()->create(['event_type' => 'DELIVERY_CONFIRMED'] + $values);
                } else {
                    $event->update($values);
                }
            }

            return $confirmation;
        });
        $this->audit->record('TRANSPORT_DELIVERY_CONFIRMED', $confirmation, null, ['transport_trip_id' => $trip->id, 'delivered_at' => $confirmation->delivered_at], $user, $trip->organization_id);

        return $confirmation;
    }
}
