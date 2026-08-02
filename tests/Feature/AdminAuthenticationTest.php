<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_sign_in_and_out_with_audited_session_rotation(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();

        $this->post('/admin/login', [
            'email' => ' ADMIN@FISHTRACE.DEMO ',
            'password' => 'FishTrace@2026',
            'remember' => true,
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue(AuditLog::query()->where('action', 'ADMIN_LOGIN_SUCCEEDED')->where('user_id', $admin->id)->exists());

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
        $this->assertTrue(AuditLog::query()->where('action', 'ADMIN_LOGOUT')->where('user_id', $admin->id)->exists());
    }

    public function test_invalid_administrator_credentials_use_a_generic_audited_error(): void
    {
        $this->seed();

        $this->from('/admin/login')->post('/admin/login', [
            'email' => 'admin@fishtrace.demo',
            'password' => 'incorrect-password',
        ])->assertRedirect('/admin/login')->assertSessionHas('login_error', 'The credentials are incorrect.');

        $this->assertGuest();
        $this->assertTrue(AuditLog::query()->where('action', 'ADMIN_LOGIN_FAILED')->exists());
    }

    public function test_non_administrator_credentials_are_denied_and_logged_out(): void
    {
        $this->seed();
        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();

        $this->post('/admin/login', [
            'email' => $fisher->email,
            'password' => 'FishTrace@2026',
        ])->assertRedirect('/admin/login')->assertSessionHas('login_error', 'Administrator access is required.');

        $this->assertGuest();
        $this->assertTrue(AuditLog::query()->where('action', 'ADMIN_LOGIN_DENIED')->where('user_id', $fisher->id)->exists());
    }
}
