<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\IotDevice;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Firebase\MockFirebaseClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSystemAndSecuritySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_manage_non_secret_runtime_settings(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $this->actingAs($admin)->get('/admin/settings/system')->assertOk()->assertSee('Operational configuration')->assertSee('Credentials, service endpoints, debug mode, and encryption keys remain environment-controlled.');

        $this->put('/admin/settings/system', ['platform_name' => 'Ocean Ledger', 'support_email' => 'support@example.test', 'consumer_portal_enabled' => '1', 'consumer_portal_notice' => 'Scheduled verification maintenance tonight.', 'telemetry_retention_hours' => 24])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseCount('system_settings', 5);
        $this->assertSame(5, AuditLog::query()->where('action', 'SYSTEM_SETTING_UPDATED')->where('user_id', $admin->id)->count());
        $this->get('/admin/settings/system')->assertOk()->assertSee('Ocean Ledger')->assertSee('support@example.test');
        $this->get('/trace/demo-trace-yellowfin-tuna-2026')->assertOk()->assertSee('Ocean Ledger verified origin')->assertSee('Scheduled verification maintenance tonight.')->assertSee('support@example.test');
        $this->getJson('/api/v1/public/trace/demo-trace-yellowfin-tuna-2026')->assertOk()->assertJsonPath('data.portal.name', 'Ocean Ledger')->assertJsonPath('data.portal.support_email', 'support@example.test');

        $this->put('/admin/settings/system', ['platform_name' => 'Ocean Ledger', 'support_email' => '', 'consumer_portal_enabled' => '0', 'consumer_portal_notice' => '', 'telemetry_retention_hours' => 24])->assertRedirect();
        $this->get('/trace/demo-trace-yellowfin-tuna-2026')->assertStatus(503);
        $this->getJson('/api/v1/public/trace/demo-trace-yellowfin-tuna-2026')->assertStatus(503);
    }

    public function test_system_setting_validation_and_authorization_are_enforced(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $this->actingAs($admin)->from('/admin/settings/system')->put('/admin/settings/system', ['platform_name' => '', 'support_email' => 'not-email', 'consumer_portal_enabled' => '1', 'telemetry_retention_hours' => 12])->assertRedirect('/admin/settings/system')->assertSessionHasErrors(['platform_name', 'support_email', 'telemetry_retention_hours']);
        $this->assertDatabaseCount('system_settings', 0);

        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $this->actingAs($fisher)->get('/admin/settings/system')->assertForbidden();
        $this->put('/admin/settings/system', ['platform_name' => 'Forbidden', 'consumer_portal_enabled' => '1', 'telemetry_retention_hours' => 48])->assertForbidden();
        $this->assertDatabaseMissing('system_settings', ['key' => 'platform_name']);
    }

    public function test_configured_telemetry_retention_controls_cleanup(): void
    {
        $this->seed();
        SystemSetting::create(['key' => 'telemetry_retention_hours', 'value' => '24']);
        $device = IotDevice::query()->firstOrFail();
        $firebase = app(MockFirebaseClient::class);
        $firebase->set('telemetry/'.$device->firebase_uid.'/old-message', ['syncStatus' => 'SYNCED', 'recordedAt' => now()->subHours(25)->getTimestampMs()]);
        $firebase->set('telemetry/'.$device->firebase_uid.'/recent-message', ['syncStatus' => 'SYNCED', 'recordedAt' => now()->subHours(23)->getTimestampMs()]);
        $firebase->set('telemetry/'.$device->firebase_uid.'/pending-message', ['syncStatus' => 'PENDING', 'recordedAt' => now()->subHours(30)->getTimestampMs()]);

        $this->artisan('fishtrace:cleanup-firebase-telemetry')->assertSuccessful();

        $this->assertSame([], $firebase->get('telemetry/'.$device->firebase_uid.'/old-message'));
        $this->assertSame('SYNCED', $firebase->get('telemetry/'.$device->firebase_uid.'/recent-message')['syncStatus']);
        $this->assertSame('PENDING', $firebase->get('telemetry/'.$device->firebase_uid.'/pending-message')['syncStatus']);
    }

    public function test_administrator_can_update_own_profile_without_changing_access(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $roleIds = $admin->roles()->pluck('roles.id')->all();
        $organizationIds = $admin->organizations()->pluck('organizations.id')->all();
        $this->actingAs($admin)->get('/admin/profile')->assertOk()->assertSee('Administrator profile')->assertSee('System Administrator');
        $this->put('/admin/profile', ['name' => 'Platform Administrator', 'email' => 'platform.admin@example.test'])->assertRedirect()->assertSessionHas('success');

        $admin->refresh();
        $this->assertSame('Platform Administrator', $admin->name);
        $this->assertSame('platform.admin@example.test', $admin->email);
        $this->assertSame($roleIds, $admin->roles()->pluck('roles.id')->all());
        $this->assertSame($organizationIds, $admin->organizations()->pluck('organizations.id')->all());
        $this->assertTrue(AuditLog::query()->where('action', 'ADMIN_PROFILE_UPDATED')->where('auditable_id', $admin->id)->exists());
        $other = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $this->put('/admin/profile', ['name' => 'Duplicate', 'email' => $other->email])->assertSessionHasErrors('email');
    }

    public function test_password_change_and_session_controls_require_current_password(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $admin->createToken('mobile');
        $this->actingAs($admin);
        $currentSessionId = session()->getId();
        DB::table('sessions')->insert(['id' => 'other-admin-session', 'user_id' => $admin->id, 'ip_address' => '192.0.2.10', 'user_agent' => 'Other browser', 'payload' => 'serialized', 'last_activity' => now()->subMinute()->timestamp]);
        $this->get('/admin/security')->assertOk()->assertSee('Security settings')->assertSee('Other browser')->assertSee('API tokens: 1');
        $this->from('/admin/security')->put('/admin/security/password', ['current_password' => 'wrong-password', 'password' => 'ReplacementPass2026', 'password_confirmation' => 'ReplacementPass2026'])->assertRedirect('/admin/security')->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('FishTrace@2026', $admin->fresh()->password));

        $this->put('/admin/security/password', ['current_password' => 'FishTrace@2026', 'password' => 'ReplacementPass2026', 'password_confirmation' => 'ReplacementPass2026'])->assertRedirect()->assertSessionHas('success');
        $this->assertTrue(Hash::check('ReplacementPass2026', $admin->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseMissing('sessions', ['id' => 'other-admin-session']);
        $this->assertAuthenticatedAs($admin);
        $this->get('/admin/profile')->assertOk();
        $this->assertNotSame($currentSessionId, session()->getId());
        $this->assertTrue(AuditLog::query()->where('action', 'ADMIN_PASSWORD_CHANGED')->where('auditable_id', $admin->id)->exists());
    }

    public function test_revoke_other_sessions_and_account_pages_are_admin_only(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $admin->createToken('tablet');
        $this->actingAs($admin);
        DB::table('sessions')->insert(['id' => 'revoke-me', 'user_id' => $admin->id, 'ip_address' => null, 'user_agent' => null, 'payload' => 'serialized', 'last_activity' => now()->timestamp]);
        $this->post('/admin/security/revoke-other-sessions', ['current_password' => 'FishTrace@2026'])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseMissing('sessions', ['id' => 'revoke-me']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertTrue(AuditLog::query()->where('action', 'ADMIN_OTHER_SESSIONS_REVOKED')->where('auditable_id', $admin->id)->exists());

        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $this->actingAs($fisher)->get('/admin/profile')->assertForbidden();
        $this->get('/admin/security')->assertForbidden();
        $this->put('/admin/profile', ['name' => 'Forbidden', 'email' => $fisher->email])->assertForbidden();
        $this->post('/admin/security/revoke-other-sessions', ['current_password' => 'FishTrace@2026'])->assertForbidden();
    }
}
