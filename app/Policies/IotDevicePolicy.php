<?php

namespace App\Policies;

use App\Models\IotDevice;
use App\Models\User;

class IotDevicePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('TRANSPORTER');
    }

    public function view(User $user, IotDevice $device): bool
    {
        return $user->hasRole('TRANSPORTER') && $user->primaryOrganization()?->id === $device->organization_id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, IotDevice $device): bool
    {
        return false;
    }
}
