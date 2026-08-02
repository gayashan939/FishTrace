<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vehicle;

class VehiclePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('TRANSPORTER');
    }

    public function view(User $user, Vehicle $vehicle): bool
    {
        return $user->hasRole('TRANSPORTER') && $user->primaryOrganization()?->id === $vehicle->organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('TRANSPORTER');
    }

    public function update(User $user, Vehicle $vehicle): bool
    {
        return $this->view($user, $vehicle);
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return $this->view($user, $vehicle);
    }
}
