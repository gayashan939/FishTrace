<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAccessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_directory_filters_reject_unapproved_query_parameters(): void
    {
        $this->seed();
        Sanctum::actingAs(User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail());

        $this->getJson('/api/v1/admin/users?sort=password&per_page=500')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['field_errors' => ['sort', 'per_page']]]);
        $this->getJson('/api/v1/admin/organizations?type=UNRECOGNIZED&direction=sideways')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['field_errors' => ['type', 'direction']]]);
    }

    public function test_admin_can_create_a_safe_role_and_organization_scoped_user(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $role = Role::query()->where('name', 'FISHER')->firstOrFail();
        $organization = Organization::query()->where('type', 'FISHER')->firstOrFail();
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/admin/users', [
            'name' => 'New Fisher',
            'email' => 'new.fisher@example.test',
            'password' => 'StrongPass2026',
            'password_confirmation' => 'StrongPass2026',
            'status' => 'ACTIVE',
            'role_ids' => [$role->id],
            'organization_ids' => [$organization->id],
            'primary_organization_id' => $organization->id,
        ]);

        $response->assertCreated()->assertJsonPath('data.email', 'new.fisher@example.test')->assertJsonPath('data.roles.0.name', 'FISHER')->assertJsonPath('data.organizations.0.is_primary', true)->assertJsonMissing(['password'])->assertJsonMissing(['firebase_uid']);
        $user = User::query()->where('email', 'new.fisher@example.test')->firstOrFail();
        $this->assertSame('user:'.$user->id, $user->firebase_uid);
        $this->assertDatabaseHas('organization_user', ['user_id' => $user->id, 'organization_id' => $organization->id, 'is_primary' => true]);
        $this->assertTrue(AuditLog::query()->where('action', 'USER_ACCESS_ASSIGNED')->where('auditable_id', $user->id)->exists());
    }

    public function test_role_assignment_requires_a_matching_active_organization(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $processorRole = Role::query()->where('name', 'PROCESSOR')->firstOrFail();
        $fisherOrganization = Organization::query()->where('type', 'FISHER')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/admin/users', [
            'name' => 'Invalid Assignment',
            'email' => 'invalid@example.test',
            'password' => 'StrongPass2026',
            'password_confirmation' => 'StrongPass2026',
            'status' => 'ACTIVE',
            'role_ids' => [$processorRole->id],
            'organization_ids' => [$fisherOrganization->id],
            'primary_organization_id' => $fisherOrganization->id,
        ])->assertStatus(422)->assertJsonPath('error.message', 'PROCESSOR requires an assigned PROCESSOR organization.');

        $this->assertDatabaseMissing('users', ['email' => 'invalid@example.test']);
    }

    public function test_access_update_and_security_actions_revoke_sessions(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $target = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        $role = Role::query()->where('name', 'FISHER')->firstOrFail();
        $organization = Organization::query()->where('type', 'FISHER')->firstOrFail();
        $target->createToken('mobile');
        Sanctum::actingAs($admin);

        $this->putJson('/api/v1/admin/users/'.$target->id, ['name' => 'Updated Fisher', 'email' => $target->email, 'role_ids' => [$role->id], 'organization_ids' => [$organization->id], 'primary_organization_id' => $organization->id])->assertOk()->assertJsonPath('data.name', 'Updated Fisher');
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $target->update(['failed_login_count' => 5, 'locked_until' => now()->addHour()]);
        $this->postJson('/api/v1/admin/users/'.$target->id.'/unlock')->assertOk()->assertJsonPath('data.is_locked', false);
        $this->postJson('/api/v1/admin/users/'.$target->id.'/deactivate')->assertOk()->assertJsonPath('data.status', 'DISABLED');
        $this->postJson('/api/v1/admin/users/'.$target->id.'/activate')->assertOk()->assertJsonPath('data.status', 'ACTIVE');
        $this->postJson('/api/v1/admin/users/'.$target->id.'/reset-password', ['password' => 'Replacement2026', 'password_confirmation' => 'Replacement2026'])->assertOk()->assertJsonPath('data.sessions_revoked', true);
        $this->assertSame(0, $target->fresh()->failed_login_count);
    }

    public function test_administrator_cannot_disable_their_own_access(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/admin/users/'.$admin->id.'/deactivate')->assertStatus(409);
        $this->assertSame('ACTIVE', $admin->fresh()->status);
    }

    public function test_organization_lifecycle_protects_active_members(): void
    {
        $this->seed();
        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($admin);

        $created = $this->postJson('/api/v1/admin/organizations', ['name' => 'New Fisher Cooperative', 'code' => 'NFC-001', 'type' => 'FISHER'])->assertCreated()->assertJsonPath('data.is_active', true);
        $organizationId = $created->json('data.id');
        $this->postJson('/api/v1/admin/organizations/'.$organizationId.'/deactivate')->assertOk()->assertJsonPath('data.is_active', false);

        $assigned = Organization::query()->where('type', 'FISHER')->where('id', '!=', $organizationId)->firstOrFail();
        $this->postJson('/api/v1/admin/organizations/'.$assigned->id.'/deactivate')->assertStatus(409);
        $this->assertTrue($assigned->fresh()->is_active);
    }

    public function test_non_admin_is_forbidden_and_admin_pages_are_functional(): void
    {
        $this->seed();
        $fisher = User::query()->where('email', 'fisher@fishtrace.demo')->firstOrFail();
        Sanctum::actingAs($fisher);
        $this->getJson('/api/v1/admin/users')->assertForbidden();

        $admin = User::query()->where('email', 'admin@fishtrace.demo')->firstOrFail();
        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('System Administrator')->assertSee('Add user');
        $this->actingAs($admin)->get('/admin/users/create')->assertOk()->assertSee('Temporary password');
        $this->actingAs($admin)->get('/admin/users/'.$admin->id)->assertOk()->assertSee('Security actions');
        $this->actingAs($admin)->get('/admin/users/'.$admin->id.'/edit')->assertOk()->assertSee('Changing access revokes');
        $organization = Organization::query()->where('type', 'REGULATOR')->firstOrFail();
        $this->actingAs($admin)->get('/admin/organizations')->assertOk()->assertSee('FishTrace National Operations')->assertSee('Add organization');
        $this->actingAs($admin)->get('/admin/organizations/create')->assertOk()->assertSee('Create organization');
        $this->actingAs($admin)->get('/admin/organizations/'.$organization->id)->assertOk()->assertSee('Assigned users');
        $this->actingAs($admin)->get('/admin/organizations/'.$organization->id.'/edit')->assertOk()->assertSee('Save changes');
        $this->actingAs($admin)->get('/admin/roles')->assertOk()->assertSee('ADMIN')->assertSee('System roles are fixed');
    }
}
