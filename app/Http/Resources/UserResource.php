<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = User::findOrFail($this->resource->getKey());
        $organization = $user->primaryOrganization();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->roles()->value('name'),
            'organization' => $organization ? [
                'id' => $organization->id,
                'name' => $organization->name,
                'code' => $organization->code,
                'type' => $organization->type,
                'is_active' => $organization->is_active,
            ] : null,
        ];
    }
}
