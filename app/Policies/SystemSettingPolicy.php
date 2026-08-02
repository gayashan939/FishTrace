<?php

namespace App\Policies;

use App\Models\SystemSetting;
use App\Models\User;

class SystemSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function update(User $user, SystemSetting $setting): bool
    {
        return $user->hasRole('ADMIN');
    }
}
