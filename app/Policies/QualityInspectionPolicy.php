<?php

namespace App\Policies;

use App\Models\QualityInspection;
use App\Models\User;

class QualityInspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN')
            || (($user->hasRole('PROCESSOR') || $user->hasRole('INSPECTOR')) && $user->primaryOrganization() !== null);
    }

    public function view(User $user, QualityInspection $inspection): bool
    {
        return ($user->hasRole('PROCESSOR') || $user->hasRole('INSPECTOR') || $user->hasRole('ADMIN')) && ($user->hasRole('ADMIN') || $user->primaryOrganization()?->id === $inspection->organization_id);
    }
}
