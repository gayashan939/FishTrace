<?php

namespace App\Policies;

use App\Models\ProcessorProfile;
use App\Models\User;

class ProcessorProfilePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('PROCESSOR') && $user->primaryOrganization() !== null;
    }

    public function view(User $user, ProcessorProfile $profile): bool
    {
        return $user->hasRole('PROCESSOR') && $user->primaryOrganization()?->id === $profile->organization_id;
    }
}
