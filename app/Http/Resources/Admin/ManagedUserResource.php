<?php

namespace App\Http\Resources\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use LogicException;

/** @mixin User */
class ManagedUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->resource;
        if (! $user instanceof User) {
            throw new LogicException('ManagedUserResource requires a User model.');
        }
        $organizations = [];
        foreach ($user->organizations as $organization) {
            if (! $organization instanceof Organization) {
                continue;
            }
            $pivot = $organization->getRelation('pivot');
            $organizations[] = [
                'id' => $organization->id,
                'name' => $organization->name,
                'code' => $organization->code,
                'type' => $organization->type,
                'is_active' => $organization->is_active,
                'is_primary' => (bool) $pivot->getAttribute('is_primary'),
            ];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'is_locked' => $user->isLocked(),
            'failed_login_count' => $user->failed_login_count,
            'locked_until' => $user->locked_until ? Carbon::parse($user->locked_until)->toIso8601String() : null,
            'last_login_at' => $user->last_login_at ? Carbon::parse($user->last_login_at)->toIso8601String() : null,
            'roles' => $this->whenLoaded('roles', fn () => $user->roles->map->only(['id', 'name'])->values()),
            'organizations' => $this->whenLoaded('organizations', $organizations),
            'active_token_count' => $this->whenCounted('tokens'),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }
}
