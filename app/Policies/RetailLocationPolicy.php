<?php

namespace App\Policies;

use App\Models\RetailLocation;
use App\Models\User;

class RetailLocationPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('RETAILER') && $user->primaryOrganization() !== null;
    }

    public function view(User $user, RetailLocation $location): bool
    {
        return $user->hasRole('RETAILER') && $user->primaryOrganization()?->id === $location->organization_id;
    }
}
