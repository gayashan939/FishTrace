<?php

namespace App\Policies;

use App\Models\TransportTrip;
use App\Models\User;

class TransportTripPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('TRANSPORTER');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('TRANSPORTER');
    }

    public function view(User $user, TransportTrip $trip): bool
    {
        return $user->hasRole('TRANSPORTER') && $user->primaryOrganization()?->id === $trip->organization_id;
    }

    public function update(User $user, TransportTrip $trip): bool
    {
        return $this->view($user, $trip);
    }
}
