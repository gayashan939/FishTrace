<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN') || $user->hasRole('INSPECTOR');
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        if ($user->hasRole('ADMIN')) {
            return true;
        }

        return $user->hasRole('INSPECTOR') && $auditLog->organization_id === $user->primaryOrganization()?->id;
    }
}
