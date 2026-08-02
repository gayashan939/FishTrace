<?php

namespace App\Actions\Admin;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class ManageOrganization
{
    public function __construct(private AuditLogger $audit) {}

    public function create(User $actor, array $data): Organization
    {
        $organization = Organization::create(['name' => $data['name'], 'code' => $data['code'], 'type' => OrganizationType::from($data['type'])->value, 'is_active' => true]);
        $this->audit->record('ORGANIZATION_ACCESS_SCOPE_CREATED', $organization, null, $organization->getAttributes(), $actor, $actor->primaryOrganization()?->id);

        return $organization;
    }

    public function update(User $actor, Organization $organization, array $data): Organization
    {
        if ($organization->type !== $data['type']) {
            abort_if($organization->users()->exists(), 409, 'An organization type cannot change after users are assigned.');
        }
        $organization->update(['name' => $data['name'], 'code' => $data['code'], 'type' => OrganizationType::from($data['type'])->value]);

        return $organization->fresh();
    }

    public function setActive(User $actor, Organization $organization, bool $active): Organization
    {
        return DB::transaction(function () use ($actor, $organization, $active): Organization {
            $locked = Organization::query()->lockForUpdate()->findOrFail($organization->id);
            if (! $active) {
                abort_if($locked->users()->where('status', 'ACTIVE')->exists(), 409, 'Disable or reassign active users before deactivating this organization.');
            }
            $locked->update(['is_active' => $active]);
            $this->audit->record('ORGANIZATION_STATUS_CHANGED', $locked, ['is_active' => ! $active], ['is_active' => $active], $actor, $actor->primaryOrganization()?->id);

            return $locked->fresh();
        });
    }
}
