<?php

namespace App\Policies;

use App\Models\ReportExport;
use App\Models\User;

class ReportExportPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->roles->isNotEmpty() && $user->primaryOrganization() !== null;
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function view(User $user, ReportExport $export): bool
    {
        return $export->organization_id === $user->primaryOrganization()?->id;
    }
}
