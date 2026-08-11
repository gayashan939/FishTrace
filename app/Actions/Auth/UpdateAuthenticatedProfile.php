<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateAuthenticatedProfile
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $actor, array $attributes): User
    {
        return DB::transaction(function () use ($actor, $attributes): User {
            $user = User::query()->lockForUpdate()->findOrFail($actor->id);
            $before = $user->only(['name', 'email']);
            $user->update([
                'name' => $attributes['name'],
                'email' => mb_strtolower($attributes['email']),
            ]);
            $this->audit->record(
                'PROFILE_UPDATED',
                $user,
                $before,
                $user->only(['name', 'email']),
                $actor,
                $actor->primaryOrganization()?->id,
            );

            return $user->fresh(['roles', 'organizations']);
        });
    }
}
