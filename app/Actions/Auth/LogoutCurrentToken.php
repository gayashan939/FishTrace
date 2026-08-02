<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Audit\AuditLogger;

class LogoutCurrentToken
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(User $user): void
    {
        $this->audit->record('AUTH_LOGOUT', $user, null, ['scope' => 'CURRENT_TOKEN'], $user);
        $user->currentAccessToken()->delete();
    }
}
