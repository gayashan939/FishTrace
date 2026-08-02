<?php

namespace App\Policies;

use App\Models\FileAsset;
use App\Models\User;

class FileAssetPolicy
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
        return $user->roles->isNotEmpty() && $user->primaryOrganization() !== null;
    }

    public function view(User $user, FileAsset $file): bool
    {
        return $file->organization_id === $user->primaryOrganization()?->id;
    }

    public function delete(User $user, FileAsset $file): bool
    {
        return $this->view($user, $file) && ($file->uploaded_by === $user->id || $user->hasRole('ADMIN'));
    }
}
