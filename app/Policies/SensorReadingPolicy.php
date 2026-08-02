<?php

namespace App\Policies;

use App\Models\SensorReading;
use App\Models\User;

class SensorReadingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function view(User $user, SensorReading $reading): bool
    {
        return $user->hasRole('ADMIN');
    }
}
