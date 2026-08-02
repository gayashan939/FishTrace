<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_fisher_can_login_and_receive_expected_contract(): void
    {
        $this->seed();
        $response = $this->postJson('/api/v1/auth/login', ['email' => 'fisher@fishtrace.demo', 'password' => DatabaseSeeder::DEMO_PASSWORD, 'device_name' => 'Flutter test']);
        $response->assertOk()->assertJsonPath('data.token_type', 'Bearer')->assertJsonPath('data.user.role', 'FISHER')->assertJsonPath('data.user.organization.name', 'Southern Fisheries Cooperative')->assertJsonStructure(['data' => ['token', 'user'], 'meta' => ['request_id']]);
        $token = $response->json('data.token');
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.email', 'fisher@fishtrace.demo');
    }

    public function test_invalid_and_disabled_accounts_cannot_login(): void
    {
        $this->seed();
        $this->postJson('/api/v1/auth/login', ['email' => 'fisher@fishtrace.demo', 'password' => 'wrong-password', 'device_name' => 'Flutter'])->assertUnprocessable();
        User::where('email', 'fisher@fishtrace.demo')->update(['status' => 'DISABLED']);
        $this->postJson('/api/v1/auth/login', ['email' => 'fisher@fishtrace.demo', 'password' => DatabaseSeeder::DEMO_PASSWORD, 'device_name' => 'Flutter'])->assertUnprocessable();
    }

    public function test_authenticated_user_receives_mock_firebase_session(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $user->forceFill(['firebase_uid' => null])->saveQuietly();
        $token = $this->postJson('/api/v1/auth/login', ['email' => 'fisher@fishtrace.demo', 'password' => DatabaseSeeder::DEMO_PASSWORD, 'device_name' => 'Flutter'])->json('data.token');
        $this->withToken($token)->postJson('/api/v1/firebase/session')->assertOk()->assertJsonPath('data.database_url', 'https://mock.local')->assertJsonPath('data.expires_in', 3600)->assertJsonStructure(['data' => ['firebase_custom_token', 'firebase_uid']]);
        $this->assertSame('user:'.$user->id, $user->fresh()->firebase_uid);
        $this->assertTrue(AuditLog::query()->where('action', 'FIREBASE_IDENTITY_ASSIGNED')->where('auditable_id', $user->id)->exists());
    }
}
