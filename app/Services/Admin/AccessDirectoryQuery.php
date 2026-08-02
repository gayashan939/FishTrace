<?php

namespace App\Services\Admin;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AccessDirectoryQuery
{
    public function paginatedUsers(array $filters): LengthAwarePaginator
    {
        return $this->users($filters)->paginate((int) ($filters['per_page'] ?? 25))->withQueryString();
    }

    public function user(User $user): User
    {
        return $user->load(['roles:id,name', 'organizations:id,name,code,type,is_active'])->loadCount('tokens');
    }

    public function paginatedOrganizations(array $filters): LengthAwarePaginator
    {
        return $this->organizations($filters)->paginate((int) ($filters['per_page'] ?? 25))->withQueryString();
    }

    public function organization(Organization $organization): Organization
    {
        return $organization->loadCount('users');
    }

    public function roles(): Collection
    {
        return Role::query()->withCount('users')->orderBy('name')->get();
    }

    public function organizationOptions(bool $activeOnly = false): Collection
    {
        return Organization::query()
            ->when($activeOnly, fn (Builder $query): Builder => $query->where('is_active', true))
            ->orderBy('name')
            ->get();
    }

    public function role(Role $role): Role
    {
        return $role->loadCount('users');
    }

    public function users(array $filters): Builder
    {
        $sort = in_array($filters['sort'] ?? null, ['name', 'email', 'status', 'last_login_at', 'created_at'], true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        return User::query()
            ->with(['roles:id,name', 'organizations:id,name,code,type,is_active'])
            ->withCount('tokens')
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['role_id'] ?? null, fn (Builder $query, string $roleId): Builder => $query->whereHas('roles', fn (Builder $roles): Builder => $roles->where('roles.id', $roleId)))
            ->when($filters['organization_id'] ?? null, fn (Builder $query, string $organizationId): Builder => $query->whereHas('organizations', fn (Builder $organizations): Builder => $organizations->where('organizations.id', $organizationId)))
            ->when(isset($filters['locked']), function (Builder $query) use ($filters): void {
                $filters['locked'] ? $query->where('locked_until', '>', now()) : $query->where(fn (Builder $query): Builder => $query->whereNull('locked_until')->orWhere('locked_until', '<=', now()));
            })
            ->orderBy($sort, $direction)
            ->orderBy('id');
    }

    public function organizations(array $filters): Builder
    {
        $sort = in_array($filters['sort'] ?? null, ['name', 'code', 'type', 'created_at'], true) ? $filters['sort'] : 'name';
        $direction = ($filters['direction'] ?? null) === 'desc' ? 'desc' : 'asc';

        return Organization::query()
            ->withCount('users')
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%");
                });
            })
            ->when($filters['type'] ?? null, fn (Builder $query, string $type): Builder => $query->where('type', $type))
            ->when(isset($filters['is_active']), fn (Builder $query): Builder => $query->where('is_active', $filters['is_active']))
            ->orderBy($sort, $direction)
            ->orderBy('id');
    }
}
