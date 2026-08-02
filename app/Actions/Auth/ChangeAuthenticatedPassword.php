<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ChangeAuthenticatedPassword
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function execute(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $locked->update(['password' => Hash::make($password)]);
            $locked->tokens()->delete();
            $this->audit->record('AUTH_PASSWORD_CHANGED', $locked, null, ['tokens_revoked' => true], $locked);
        });
    }
}
