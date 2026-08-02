<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateUser
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(string $email, string $password, string $deviceName): array
    {
        $user = User::query()->with(['roles', 'organizations'])->where('email', mb_strtolower($email))->first();
        if (! $user || ! Hash::check($password, $user->password)) {
            if ($user) {
                $user->increment('failed_login_count');
            }
            $this->audit->record('AUTH_LOGIN_FAILED', $user, null, ['email_hash' => hash('sha256', mb_strtolower($email)), 'device_name' => $deviceName, 'reason' => 'INVALID_CREDENTIALS'], null, $user?->primaryOrganization()?->id);
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }
        if ($user->status !== 'ACTIVE' || $user->isLocked()) {
            $this->audit->record('AUTH_LOGIN_BLOCKED', $user, null, ['device_name' => $deviceName, 'reason' => $user->status !== 'ACTIVE' ? 'INACTIVE' : 'LOCKED'], null, $user->primaryOrganization()?->id);
            throw ValidationException::withMessages(['email' => ['This account is not available.']]);
        }
        $user->forceFill(['last_login_at' => now(), 'failed_login_count' => 0, 'locked_until' => null])->save();
        $this->audit->record('AUTH_LOGIN_SUCCEEDED', $user, null, ['device_name' => $deviceName], $user, $user->primaryOrganization()?->id);

        return ['token' => $user->createToken($deviceName)->plainTextToken, 'user' => $user];
    }
}
