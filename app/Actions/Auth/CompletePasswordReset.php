<?php

namespace App\Actions\Auth;

use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CompletePasswordReset
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(string $email, string $resetToken, string $password): void
    {
        $normalizedEmail = mb_strtolower(trim($email));
        $user = DB::transaction(function () use ($normalizedEmail, $resetToken, $password): ?User {
            $record = PasswordResetOtp::query()
                ->where('email', $normalizedEmail)
                ->where('verification_token_hash', hash('sha256', $resetToken))
                ->whereNotNull('verified_at')
                ->latest()
                ->lockForUpdate()
                ->first();

            if ($record === null || $record->isExpired()) {
                return null;
            }

            $user = User::query()->where('email', $normalizedEmail)->lockForUpdate()->first();
            if ($user === null) {
                return null;
            }

            $user->update(['password' => Hash::make($password)]);
            $user->tokens()->delete();
            PasswordResetOtp::query()->where('email', $normalizedEmail)->delete();

            return $user;
        });

        if ($user === null) {
            $this->audit->record('AUTH_PASSWORD_RESET_FAILED', null, null, ['email_hash' => hash('sha256', $normalizedEmail), 'reason' => 'INVALID_OR_EXPIRED_TOKEN']);
            throw ValidationException::withMessages(['reset_token' => ['The reset token is invalid or expired.']]);
        }

        $this->audit->record('AUTH_PASSWORD_RESET_COMPLETED', $user, null, ['tokens_revoked' => true], $user, $user->primaryOrganization()?->id);
    }
}
