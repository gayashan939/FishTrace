<?php

namespace App\Policies;

use App\Models\Boat;
use App\Models\User;

class BoatPolicy
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

    public function view(User $user, Boat $boat): bool
    {
        return $user->hasRole('FISHER') && $user->primaryOrganization()?->id === $boat->organization_id;
    }

    public function update(User $user, Boat $boat): bool
    {
        return $this->view($user, $boat) && $boat->owner_id === $user->id;
    }

    public function delete(User $user, Boat $boat): bool
    {
        return $this->update($user, $boat);
    }
}
