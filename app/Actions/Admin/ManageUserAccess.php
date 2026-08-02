<?php

namespace App\Actions\Admin;

use App\Enums\UserStatus;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ManageUserAccess
{
    private const ROLE_ORGANIZATION_TYPES = ['ADMIN' => 'REGULATOR', 'FISHER' => 'FISHER', 'PROCESSOR' => 'PROCESSOR', 'TRANSPORTER' => 'TRANSPORTER', 'RETAILER' => 'RETAILER', 'INSPECTOR' => 'INSPECTOR'];

    public function __construct(private AuditLogger $audit) {}

    public function create(User $actor, array $data): User
    {
        return DB::transaction(function () use ($actor, $data): User {
            $this->validateAccess($data['role_ids'], $data['organization_ids'], $data['primary_organization_id']);
            $user = User::create(['name' => $data['name'], 'email' => mb_strtolower($data['email']), 'password' => Hash::make($data['password']), 'status' => UserStatus::from($data['status'])->value]);
            $user->update(['firebase_uid' => 'user:'.$user->id]);
            $this->syncAccess($user, $data['role_ids'], $data['organization_ids'], $data['primary_organization_id']);
            $this->audit->record('USER_ACCESS_ASSIGNED', $user, null, ['role_ids' => $data['role_ids'], 'organization_ids' => $data['organization_ids'], 'primary_organization_id' => $data['primary_organization_id']], $actor, $actor->primaryOrganization()?->id);

            return $user->load(['roles', 'organizations']);
        });
    }

    public function update(User $actor, User $target, array $data): User
    {
        return DB::transaction(function () use ($actor, $target, $data): User {
            $locked = User::query()->lockForUpdate()->findOrFail($target->id);
            $oldRoleIds = $locked->roles()->pluck('roles.id')->all();
            $oldOrganizationIds = $locked->organizations()->pluck('organizations.id')->all();
            $oldPrimaryId = $locked->primaryOrganization()?->id;
            $this->validateAccess($data['role_ids'], $data['organization_ids'], $data['primary_organization_id']);
            $willBeAdmin = Role::query()->whereIn('id', $data['role_ids'])->where('name', 'ADMIN')->exists();
            if ($locked->hasRole('ADMIN') && ! $willBeAdmin) {
                $this->guardAdminContinuity($actor, $locked);
            }
            $locked->update(['name' => $data['name'], 'email' => mb_strtolower($data['email'])]);
            $this->syncAccess($locked, $data['role_ids'], $data['organization_ids'], $data['primary_organization_id']);
            $this->revokeSessionsInternal($locked);
            $this->audit->record('USER_ACCESS_UPDATED', $locked, ['role_ids' => $oldRoleIds, 'organization_ids' => $oldOrganizationIds, 'primary_organization_id' => $oldPrimaryId], ['role_ids' => $data['role_ids'], 'organization_ids' => $data['organization_ids'], 'primary_organization_id' => $data['primary_organization_id']], $actor, $actor->primaryOrganization()?->id);

            return $locked->load(['roles', 'organizations']);
        });
    }

    public function setStatus(User $actor, User $target, UserStatus $status): User
    {
        return DB::transaction(function () use ($actor, $target, $status): User {
            $locked = User::query()->lockForUpdate()->findOrFail($target->id);
            if ($status === UserStatus::DISABLED && $locked->hasRole('ADMIN')) {
                $this->guardAdminContinuity($actor, $locked);
            }
            if ($status === UserStatus::ACTIVE) {
                abort_if($locked->organizations()->where('is_active', false)->exists(), 409, 'All assigned organizations must be active before this user can be activated.');
            }
            $oldStatus = $locked->status;
            $locked->update(['status' => $status->value]);
            if ($status === UserStatus::DISABLED) {
                $this->revokeSessionsInternal($locked);
            }
            $this->audit->record('USER_STATUS_CHANGED', $locked, ['status' => $oldStatus], ['status' => $status->value], $actor, $actor->primaryOrganization()?->id);

            return $locked->fresh(['roles', 'organizations']);
        });
    }

    public function unlock(User $actor, User $target): User
    {
        $target->update(['failed_login_count' => 0, 'locked_until' => null]);
        $this->audit->record('USER_UNLOCKED', $target, null, ['failed_login_count' => 0, 'locked_until' => null], $actor, $actor->primaryOrganization()?->id);

        return $target->fresh(['roles', 'organizations']);
    }

    public function revokeSessions(User $actor, User $target): int
    {
        $count = $target->tokens()->count() + DB::table('sessions')->where('user_id', $target->id)->count();
        $this->revokeSessionsInternal($target);
        $this->audit->record('USER_SESSIONS_REVOKED', $target, null, ['revoked_session_count' => $count], $actor, $actor->primaryOrganization()?->id);

        return $count;
    }

    public function resetPassword(User $actor, User $target, string $password): void
    {
        DB::transaction(function () use ($actor, $target, $password): void {
            $target->update(['password' => Hash::make($password)]);
            $this->revokeSessionsInternal($target);
            $this->audit->record('ADMIN_PASSWORD_RESET', $target, null, ['tokens_revoked' => true], $actor, $actor->primaryOrganization()?->id);
        });
    }

    private function validateAccess(array $roleIds, array $organizationIds, string $primaryOrganizationId): void
    {
        abort_unless(in_array($primaryOrganizationId, $organizationIds, true), 422, 'The primary organization must be assigned to the user.');
        $roles = Role::query()->whereIn('id', $roleIds)->get();
        $organizations = Organization::query()->whereIn('id', $organizationIds)->where('is_active', true)->get();
        abort_unless($roles->count() === count($roleIds) && $organizations->count() === count($organizationIds), 422, 'All roles and organizations must exist and organizations must be active.');
        $organizationTypes = $organizations->pluck('type')->all();
        foreach ($roles as $role) {
            $requiredType = self::ROLE_ORGANIZATION_TYPES[$role->name] ?? null;
            abort_unless($requiredType !== null && in_array($requiredType, $organizationTypes, true), 422, $role->name.' requires an assigned '.$requiredType.' organization.');
        }
    }

    private function syncAccess(User $user, array $roleIds, array $organizationIds, string $primaryOrganizationId): void
    {
        $user->roles()->sync($roleIds);
        $memberships = collect($organizationIds)->mapWithKeys(fn (string $id): array => [$id => ['is_primary' => $id === $primaryOrganizationId]])->all();
        $user->organizations()->sync($memberships);
        $user->unsetRelation('roles')->unsetRelation('organizations');
    }

    private function guardAdminContinuity(User $actor, User $target): void
    {
        abort_if($actor->id === $target->id, 409, 'You cannot remove or disable your own administrator access.');
        $activeAdmins = User::query()->where('status', UserStatus::ACTIVE->value)->whereHas('roles', fn ($query) => $query->where('name', 'ADMIN'))->count();
        abort_if($activeAdmins <= 1, 409, 'At least one active administrator must remain.');
    }

    private function revokeSessionsInternal(User $user): void
    {
        $user->tokens()->delete();
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->forceFill(['remember_token' => null])->saveQuietly();
    }
}
