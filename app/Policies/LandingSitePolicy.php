<?php

namespace App\Policies;

use App\Models\LandingSite;
use App\Models\User;

class LandingSitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function update(User $user, LandingSite $site): bool
    {
        return $user->hasRole('ADMIN');
    }
}
