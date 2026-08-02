<?php

namespace App\Policies;

use App\Models\BatchIntake;
use App\Models\User;

class BatchIntakePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('PROCESSOR') && $user->primaryOrganization() !== null;
    }

    public function view(User $user, BatchIntake $intake): bool
    {
        return $user->hasRole('PROCESSOR') && $user->primaryOrganization()?->id === $intake->processor_organization_id;
    }
}
