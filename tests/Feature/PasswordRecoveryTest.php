<?php

namespace Tests\Feature;

use App\Models\PasswordResetOtp;
use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_request_is_case_normalized_and_enumeration_safe(): void
    {
        $this->seed();
        Notification::fake();

        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => '  FISHER@FISHTRACE.DEMO '])->assertOk();
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'missing@example.test'])->assertOk();

        $this->assertSame($known->json('data.message'), $unknown->json('data.message'));
        $this->assertDatabaseCount('password_reset_otps', 1);
        $this->assertDatabaseHas('password_reset_otps', ['email' => 'fisher@fishtrace.demo', 'attempts' => 0]);
        Notification::assertCount(1);
        Notification::assertSentOnDemand(PasswordResetOtpNotification::class);
        $this->assertDatabaseCount('audit_logs', 2);
    }

    public function test_disabled_account_receives_the_same_response_without_a_code(): void
    {
        $this->seed();
        Notification::fake();
        User::where('email', 'fisher@fishtrace.demo')->update(['status' => 'DISABLED']);

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'fisher@fishtrace.demo'])->assertOk();

        $this->assertDatabaseCount('password_reset_otps', 0);
        Notification::assertNothingSent();
    }

    public function test_valid_otp_issues_only_a_hashed_reset_token(): void
    {
        $this->seed();
        $record = $this->otp('fisher@fishtrace.demo', '123456');

        $token = $this->postJson('/api/v1/auth/verify-otp', ['email' => 'FISHER@FISHTRACE.DEMO', 'otp' => '123456'])
            ->assertOk()
            ->assertJsonPath('data.expires_in', fn ($seconds): bool => is_int($seconds) && $seconds > 0 && $seconds <= 600)
            ->json('data.reset_token');

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));
        $record->refresh();
        $this->assertSame(hash('sha256', $token), $record->verification_token_hash);
        $this->assertNotSame($token, $record->verification_token_hash);
        $this->assertNotNull($record->verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'AUTH_OTP_VERIFIED']);
        $this->postJson('/api/v1/auth/verify-otp', ['email' => 'fisher@fishtrace.demo', 'otp' => '123456'])->assertUnprocessable();
    }

    public function test_five_invalid_attempts_lock_the_otp_even_when_the_code_is_later_correct(): void
    {
        $this->seed();
        $record = $this->otp('fisher@fishtrace.demo', '123456');

        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/v1/auth/verify-otp', ['email' => 'fisher@fishtrace.demo', 'otp' => '654321'])->assertUnprocessable();
        }

        $record->refresh();
        $this->assertSame(5, $record->attempts);
        $this->postJson('/api/v1/auth/verify-otp', ['email' => 'fisher@fishtrace.demo', 'otp' => '123456'])->assertUnprocessable();
        $this->assertNull($record->fresh()->verified_at);
        $this->assertDatabaseCount('audit_logs', 6);
    }

    public function test_expired_otp_and_unknown_account_share_the_generic_failure(): void
    {
        $this->seed();
        $this->otp('fisher@fishtrace.demo', '123456', now()->subSecond());

        $expired = $this->postJson('/api/v1/auth/verify-otp', ['email' => 'fisher@fishtrace.demo', 'otp' => '123456'])->assertUnprocessable();
        $unknown = $this->postJson('/api/v1/auth/verify-otp', ['email' => 'missing@example.test', 'otp' => '123456'])->assertUnprocessable();

        $this->assertSame(['The verification code is invalid or expired.'], $expired->json('error.field_errors.otp'));
        $this->assertSame($expired->json('error.field_errors.otp'), $unknown->json('error.field_errors.otp'));
    }

    public function test_verified_token_resets_password_once_and_revokes_all_sessions(): void
    {
        $this->seed();
        $user = User::where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $user->createToken('phone');
        $user->createToken('tablet');
        $this->otp($user->email, '123456');
        $resetToken = $this->postJson('/api/v1/auth/verify-otp', ['email' => $user->email, 'otp' => '123456'])->assertOk()->json('data.reset_token');
        $payload = [
            'email' => $user->email,
            'reset_token' => $resetToken,
            'password' => 'NewSecure1234',
            'password_confirmation' => 'NewSecure1234',
        ];

        $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk();

        $this->assertTrue(Hash::check('NewSecure1234', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseCount('password_reset_otps', 0);
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertUnprocessable();
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => DatabaseSeeder::DEMO_PASSWORD, 'device_name' => 'old'])->assertUnprocessable();
        $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'NewSecure1234', 'device_name' => 'new'])->assertOk();
        $this->assertDatabaseHas('audit_logs', ['action' => 'AUTH_PASSWORD_RESET_COMPLETED', 'user_id' => $user->id]);
    }

    public function test_reset_token_is_bound_to_email_expiry_and_password_policy(): void
    {
        $this->seed();
        $this->otp('fisher@fishtrace.demo', '123456');
        $token = $this->postJson('/api/v1/auth/verify-otp', ['email' => 'fisher@fishtrace.demo', 'otp' => '123456'])->assertOk()->json('data.reset_token');

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'retailer@fishtrace.demo',
            'reset_token' => $token,
            'password' => 'NewSecure1234',
            'password_confirmation' => 'NewSecure1234',
        ])->assertUnprocessable();
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'fisher@fishtrace.demo',
            'reset_token' => $token,
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertUnprocessable()->assertJsonPath('error.field_errors.password.0', fn ($message): bool => is_string($message) && $message !== '');

        PasswordResetOtp::query()->update(['expires_at' => now()->subSecond()]);
        $this->postJson('/api/v1/auth/reset-password', [
            'email' => 'fisher@fishtrace.demo',
            'reset_token' => $token,
            'password' => 'NewSecure1234',
            'password_confirmation' => 'NewSecure1234',
        ])->assertUnprocessable();
    }

    private function otp(string $email, string $otp, mixed $expiresAt = null): PasswordResetOtp
    {
        return PasswordResetOtp::create([
            'email' => $email,
            'otp_hash' => Hash::make($otp),
            'expires_at' => $expiresAt ?? now()->addMinutes(10),
        ]);
    }
}
