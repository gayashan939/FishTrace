<?php

namespace App\Policies;

use App\Models\CatchRecord;
use App\Models\User;

class CatchRecordPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('FISHER');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('FISHER');
    }

    public function view(User $user, CatchRecord $record): bool
    {
        return $user->hasRole('FISHER') && $user->primaryOrganization()?->id === $record->organization_id;
    }

    public function update(User $user, CatchRecord $record): bool
    {
        return $this->view($user, $record) && $record->trip()->where('fisher_id', $user->id)->exists();
    }

    public function delete(User $user, CatchRecord $record): bool
    {
        return $this->update($user, $record);
    }
}
