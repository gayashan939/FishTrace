<?php

namespace App\Actions\Auth;

use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Throwable;

class RequestPasswordReset
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(string $email): void
    {
        $normalizedEmail = mb_strtolower(trim($email));
        $user = User::query()->where('email', $normalizedEmail)->where('status', 'ACTIVE')->first();
        $otp = null;

        if ($user !== null) {
            $otp = (string) random_int(100000, 999999);
            DB::transaction(function () use ($normalizedEmail, $otp): void {
                PasswordResetOtp::query()->where('email', $normalizedEmail)->delete();
                PasswordResetOtp::create([
                    'email' => $normalizedEmail,
                    'otp_hash' => Hash::make($otp),
                    'expires_at' => now()->addMinutes(10),
                ]);
            });
        }

        $emailHash = hash('sha256', $normalizedEmail);
        $this->audit->record('AUTH_PASSWORD_RESET_REQUESTED', null, null, ['email_hash' => $emailHash]);

        if ($otp === null) {
            return;
        }

        try {
            Notification::route('mail', $normalizedEmail)->notify(new PasswordResetOtpNotification($otp));
        } catch (Throwable $exception) {
            report($exception);
            $this->audit->record('AUTH_PASSWORD_RESET_DELIVERY_FAILED', null, null, ['email_hash' => $emailHash]);
        }
    }
}
