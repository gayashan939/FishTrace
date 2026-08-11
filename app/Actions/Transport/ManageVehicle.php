<?php

namespace App\Actions\Transport;

use App\Models\User;
use App\Models\Vehicle;
use App\Services\Audit\AuditLogger;

class ManageVehicle
{
    public function __construct(private AuditLogger $audit) {}

    public function create(User $user, array $data): Vehicle
    {
        $organization = $user->primaryOrganization();
        abort_unless($organization !== null, 403);
        $vehicle = Vehicle::create($data + ['organization_id' => $organization->id]);
        $this->audit->record('VEHICLE_CREATED', $vehicle, null, $vehicle->only(['registration_number', 'name', 'capacity_tonnes', 'is_active']), $user, $organization->id);

        return $vehicle;
    }

    public function update(User $user, Vehicle $vehicle, array $data): Vehicle
    {
        if (($data['is_active'] ?? true) === false) {
            abort_if($vehicle->trips()->whereIn('status', ['DRAFT', 'READY', 'ACTIVE'])->exists(), 409, 'A vehicle with an unfinished trip cannot be deactivated.');
        }
        $old = $vehicle->only(['registration_number', 'name', 'capacity_tonnes', 'is_active']);
        $vehicle->update($data);
        $this->audit->record('VEHICLE_UPDATED', $vehicle, $old, $vehicle->only(['registration_number', 'name', 'capacity_tonnes', 'is_active']), $user, $vehicle->organization_id);

        return $vehicle->fresh();
    }

    public function delete(User $user, Vehicle $vehicle): void
    {
        abort_if($vehicle->trips()->whereIn('status', ['DRAFT', 'READY', 'ACTIVE'])->exists(), 409, 'A vehicle with an unfinished trip cannot be deleted.');
        $this->audit->record('VEHICLE_DELETED', $vehicle, $vehicle->only(['registration_number', 'name', 'capacity_tonnes', 'is_active']), null, $user, $vehicle->organization_id);
        $vehicle->delete();
    }
}
