<?php

namespace App\Policies;

use App\Models\FishSpecies;
use App\Models\User;

class FishSpeciesPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function useInFisherWorkflow(User $user): bool
    {
        return $user->hasRole('FISHER');
    }

    public function update(User $user, FishSpecies $species): bool
    {
        return $user->hasRole('ADMIN');
    }
}
