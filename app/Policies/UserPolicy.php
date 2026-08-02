<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function view(User $user, User $managedUser): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function update(User $user, User $managedUser): bool
    {
        return $user->hasRole('ADMIN');
    }
}
