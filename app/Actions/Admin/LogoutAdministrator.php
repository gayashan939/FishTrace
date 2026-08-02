<?php

namespace App\Actions\Admin;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;

class LogoutAdministrator
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(User $user, Session $session): void
    {
        $this->audit->record('ADMIN_LOGOUT', $user, null, null, $user);
        Auth::logout();
        $session->invalidate();
        $session->regenerateToken();
    }
}
