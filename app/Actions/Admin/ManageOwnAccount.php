<?php

namespace App\Actions\Admin;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ManageOwnAccount
{
    public function __construct(private AuditLogger $audit) {}

    public function updateProfile(User $user, array $attributes): User
    {
        return DB::transaction(function () use ($user, $attributes): User {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $old = $locked->only(['name', 'email']);
            $locked->update(['name' => $attributes['name'], 'email' => mb_strtolower($attributes['email'])]);
            $this->audit->record('ADMIN_PROFILE_UPDATED', $locked, $old, $locked->only(['name', 'email']), $locked);

            return $locked->fresh(['roles', 'organizations']);
        });
    }

    public function changePassword(User $user, string $password, string $currentSessionId): int
    {
        return DB::transaction(function () use ($user, $password, $currentSessionId): int {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $tokenCount = $locked->tokens()->count();
            $sessionCount = DB::table('sessions')->where('user_id', $locked->id)->where('id', '!=', $currentSessionId)->count();
            $locked->update(['password' => Hash::make($password)]);
            $locked->tokens()->delete();
            DB::table('sessions')->where('user_id', $locked->id)->where('id', '!=', $currentSessionId)->delete();
            $locked->forceFill(['remember_token' => null])->saveQuietly();
            $this->audit->record('ADMIN_PASSWORD_CHANGED', $locked, null, ['revoked_session_count' => $tokenCount + $sessionCount], $locked);

            return $tokenCount + $sessionCount;
        });
    }

    public function revokeOtherSessions(User $user, string $currentSessionId): int
    {
        return DB::transaction(function () use ($user, $currentSessionId): int {
            $tokenCount = $user->tokens()->count();
            $sessionCount = DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $currentSessionId)->count();
            $user->tokens()->delete();
            DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $currentSessionId)->delete();
            $user->forceFill(['remember_token' => null])->saveQuietly();
            $this->audit->record('ADMIN_OTHER_SESSIONS_REVOKED', $user, null, ['revoked_session_count' => $tokenCount + $sessionCount], $user);

            return $tokenCount + $sessionCount;
        });
    }
}
