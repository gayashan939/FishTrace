<?php

namespace App\Actions\Auth;

use App\Models\PasswordResetOtp;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VerifyPasswordResetOtp
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(private AuditLogger $audit) {}

    /** @return array{reset_token: string, expires_in: int} */
    public function execute(string $email, string $otp): array
    {
        $normalizedEmail = mb_strtolower(trim($email));
        $result = DB::transaction(function () use ($normalizedEmail, $otp): array {
            $record = PasswordResetOtp::query()->where('email', $normalizedEmail)->latest()->lockForUpdate()->first();
            if ($record === null) {
                return ['valid' => false, 'reason' => 'NOT_FOUND'];
            }

            if ($record->isExpired()) {
                return ['valid' => false, 'reason' => 'EXPIRED'];
            }

            if ($record->verified_at !== null) {
                return ['valid' => false, 'reason' => 'ALREADY_VERIFIED'];
            }

            if ($record->attempts >= self::MAX_ATTEMPTS) {
                return ['valid' => false, 'reason' => 'ATTEMPTS_EXHAUSTED'];
            }

            if (! Hash::check($otp, $record->otp_hash)) {
                $record->increment('attempts');

                return ['valid' => false, 'reason' => 'INVALID'];
            }

            $token = Str::random(64);
            $record->update([
                'verification_token_hash' => hash('sha256', $token),
                'verified_at' => now(),
            ]);

            return [
                'valid' => true,
                'reset_token' => $token,
                'expires_in' => max(0, (int) now()->diffInSeconds($record->expires_at, false)),
            ];
        });

        $emailHash = hash('sha256', $normalizedEmail);
        if ($result['valid'] !== true) {
            $this->audit->record('AUTH_OTP_VERIFICATION_FAILED', null, null, ['email_hash' => $emailHash, 'reason' => $result['reason']]);
            throw ValidationException::withMessages(['otp' => ['The verification code is invalid or expired.']]);
        }

        $this->audit->record('AUTH_OTP_VERIFIED', null, null, ['email_hash' => $emailHash]);

        return ['reset_token' => $result['reset_token'], 'expires_in' => $result['expires_in']];
    }
}
