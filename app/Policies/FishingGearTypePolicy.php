<?php

namespace App\Policies;

use App\Models\FishingGearType;
use App\Models\User;

class FishingGearTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function update(User $user, FishingGearType $gear): bool
    {
        return $user->hasRole('ADMIN');
    }
}
