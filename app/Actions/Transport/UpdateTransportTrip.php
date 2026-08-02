<?php

namespace App\Actions\Transport;

use App\Enums\TransportTripStatus;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateTransportTrip
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $user, TransportTrip $trip, array $data): TransportTrip
    {
        [$trip, $old] = DB::transaction(function () use ($trip, $data): array {
            $locked = TransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($locked->hasStatus(TransportTripStatus::DRAFT) || $locked->hasStatus(TransportTripStatus::READY), 409, 'Only a pre-departure trip can be edited.');
            if (isset($data['vehicle_id'])) {
                Vehicle::query()->where('organization_id', $locked->organization_id)->where('is_active', true)->lockForUpdate()->findOrFail($data['vehicle_id']);
            }
            $old = $locked->only(['vehicle_id', 'driver_name', 'origin', 'destination', 'scheduled_at']);
            $locked->update($data);

            return [$locked, $old];
        });
        $this->audit->record('TRANSPORT_TRIP_UPDATED', $trip, $old, $trip->only(array_keys($old)), $user, $trip->organization_id);

        return $trip->fresh(['vehicle', 'batches', 'activeAssignment.device', 'checklist.items']);
    }
}
