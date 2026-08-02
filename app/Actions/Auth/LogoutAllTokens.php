<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class LogoutAllTokens
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $this->audit->record('AUTH_LOGOUT', $user, null, ['scope' => 'ALL_TOKENS'], $user);
        });
    }
}
