<?php

namespace App\Actions\Transport;

use App\Enums\TransportTripStatus;
use App\Models\FishBatch;
use App\Models\TransportIncident;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class RecordTransportIncident
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $user, TransportTrip $trip, array $data): TransportIncident
    {
        $incident = DB::transaction(function () use ($user, $trip, $data): TransportIncident {
            $lockedTrip = TransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($lockedTrip->hasStatus(TransportTripStatus::ACTIVE), 409, 'Incidents can only be recorded during an active trip.');
            $incident = TransportIncident::create($data + ['transport_trip_id' => $lockedTrip->id, 'reported_by' => $user->id]);
            foreach ($lockedTrip->batches()->pluck('fish_batches.id') as $batchId) {
                FishBatch::findOrFail($batchId)->events()->create(['organization_id' => $lockedTrip->organization_id, 'actor_id' => $user->id, 'event_type' => 'TRANSPORT_INCIDENT', 'title' => 'Transport incident recorded', 'public_data' => ['type' => $incident->type, 'severity' => $incident->severity], 'private_data' => ['transport_incident_id' => $incident->id], 'occurred_at' => $incident->occurred_at]);
            }

            return $incident;
        });
        $this->audit->record('TRANSPORT_INCIDENT_RECORDED', $incident, null, ['type' => $incident->type, 'severity' => $incident->severity], $user, $trip->organization_id);

        return $incident;
    }
}
