<?php

namespace App\Actions\Transport;

use App\Enums\TransportTripStatus;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Audit\AuditLogger;
use App\Services\Transport\TransportChecklistService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateTransportTrip
{
    public function __construct(private TransportChecklistService $checklists, private AuditLogger $audit) {}

    public function execute(User $user, array $data): TransportTrip
    {
        $organization = $user->primaryOrganization();
        abort_unless($organization !== null, 403);
        $vehicle = Vehicle::query()->where('organization_id', $organization->id)->where('is_active', true)->findOrFail($data['vehicle_id']);
        $trip = DB::transaction(function () use ($user, $organization, $vehicle, $data): TransportTrip {
            $trip = TransportTrip::create($data + ['organization_id' => $organization->id, 'created_by' => $user->id, 'vehicle_id' => $vehicle->id, 'trip_code' => 'TTR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)), 'status' => TransportTripStatus::DRAFT]);
            $this->checklists->initialize($trip);

            return $trip;
        });
        $this->audit->record('TRANSPORT_TRIP_CREATED', $trip, null, $trip->only(['trip_code', 'vehicle_id', 'driver_name', 'origin', 'destination', 'estimated_distance_km', 'scheduled_at']), $user, $organization->id);

        return $trip->load('checklist.items');
    }
}
