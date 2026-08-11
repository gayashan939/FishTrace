<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileProfileContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_identity_without_changing_access(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $roleIds = $user->roles()->pluck('roles.id')->all();
        $organizationIds = $user->organizations()->pluck('organizations.id')->all();
        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/auth/profile', [
            'name' => 'Mobile Fisher',
            'email' => 'MOBILE.FISHER@EXAMPLE.TEST',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Mobile Fisher')
            ->assertJsonPath('data.email', 'mobile.fisher@example.test')
            ->assertJsonStructure(['data' => [
                'id', 'name', 'email', 'role',
                'organization' => ['id', 'name', 'code', 'type', 'is_active'],
            ]]);

        $this->assertSame($roleIds, $user->fresh()->roles()->pluck('roles.id')->all());
        $this->assertSame($organizationIds, $user->fresh()->organizations()->pluck('organizations.id')->all());
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'PROFILE_UPDATED',
            'user_id' => $user->id,
        ]);
        $this->assertStringNotContainsString('firebase_uid', $response->getContent());
    }

    public function test_profile_update_validates_unique_email(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $other = User::query()->where('id', '!=', $user->id)->firstOrFail();
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/auth/profile', [
            'name' => 'Duplicate Email',
            'email' => $other->email,
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }
}
