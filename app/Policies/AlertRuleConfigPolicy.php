<?php

namespace App\Policies;

use App\Models\AlertRuleConfig;
use App\Models\User;

class AlertRuleConfigPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('ADMIN');
    }

    public function update(User $user, AlertRuleConfig $rule): bool
    {
        return $user->hasRole('ADMIN');
    }
}
