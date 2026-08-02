<?php

namespace App\Policies;

use App\Models\AIPrediction;
use App\Models\User;

class AIPredictionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function view(User $user, AIPrediction $prediction): bool
    {
        return $user->hasRole('ADMIN');
    }
}
