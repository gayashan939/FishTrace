<?php

namespace App\Actions\Fisher;

use App\Models\Boat;
use App\Models\User;

class CreateBoat
{
    public function execute(User $user, array $attributes): Boat
    {
        $organization = $user->primaryOrganization();
        abort_unless($organization !== null, 403, 'An organization is required.');

        return Boat::query()->create(array_merge($attributes, [
            'organization_id' => $organization->id,
            'owner_id' => $user->id,
        ]));
    }
}
