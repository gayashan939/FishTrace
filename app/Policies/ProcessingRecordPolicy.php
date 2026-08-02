<?php

namespace App\Policies;

use App\Models\ProcessingRecord;
use App\Models\User;

class ProcessingRecordPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return ($user->hasRole('PROCESSOR') || $user->hasRole('INSPECTOR'))
            && $user->primaryOrganization() !== null;
    }

    public function view(User $user, ProcessingRecord $record): bool
    {
        return ($user->hasRole('PROCESSOR') || $user->hasRole('INSPECTOR')) && $user->primaryOrganization()?->id === $record->organization_id;
    }

    public function update(User $user, ProcessingRecord $record): bool
    {
        return $user->hasRole('PROCESSOR') && $user->primaryOrganization()?->id === $record->organization_id;
    }
}
