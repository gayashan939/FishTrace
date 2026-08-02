<?php

namespace App\Policies;

use App\Models\FishingTrip;
use App\Models\User;

class FishingTripPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('FISHER');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('FISHER');
    }

    public function view(User $user, FishingTrip $trip): bool
    {
        return $user->hasRole('FISHER') && $user->primaryOrganization()?->id === $trip->organization_id;
    }

    public function update(User $user, FishingTrip $trip): bool
    {
        return $this->view($user, $trip) && $trip->fisher_id === $user->id;
    }
}
