<?php

namespace App\Actions\Admin;

use App\Enums\AdminAuthenticationResult;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;

class AuthenticateAdministrator
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(string $email, string $password, bool $remember, Session $session): AdminAuthenticationResult
    {
        $email = mb_strtolower($email);

        if (! Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            $candidate = User::query()->where('email', $email)->first();
            $this->audit->record('ADMIN_LOGIN_FAILED', $candidate, null, [
                'email_hash' => hash('sha256', $email),
            ], null, $candidate?->primaryOrganization()?->id);

            return AdminAuthenticationResult::INVALID_CREDENTIALS;
        }

        $user = Auth::user();
        if (! $user instanceof User || ! $user->hasRole('ADMIN')) {
            if ($user instanceof User) {
                $this->audit->record('ADMIN_LOGIN_DENIED', $user, null, ['reason' => 'ROLE_REQUIRED'], $user);
            }
            Auth::logout();

            return AdminAuthenticationResult::ROLE_REQUIRED;
        }

        $session->regenerate();
        $this->audit->record('ADMIN_LOGIN_SUCCEEDED', $user, null, null, $user);

        return AdminAuthenticationResult::AUTHENTICATED;
    }
}
