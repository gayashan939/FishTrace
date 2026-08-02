<?php

namespace App\Actions\Firebase;

use App\Contracts\Firebase\FirebaseTokenService;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class IssueFirebaseSession
{
    public function __construct(
        private readonly FirebaseTokenService $tokens,
        private readonly AuditLogger $audit,
    ) {}

    public function execute(User $user): array
    {
        [$user, $uid] = DB::transaction(function () use ($user): array {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $user->loadMissing(['roles', 'organizations']);
            $uid = $user->firebase_uid ?: 'user:'.$user->id;

            if ($user->firebase_uid !== $uid) {
                $user->forceFill(['firebase_uid' => $uid])->save();
                $this->audit->record('FIREBASE_IDENTITY_ASSIGNED', $user, null, ['identity_assigned' => true], $user);
            }

            return [$user, $uid];
        });

        $organization = $user->primaryOrganization();
        $token = $this->tokens->customToken($uid, [
            'laravel_user_id' => $user->id,
            'role' => $user->roles()->value('name'),
            'organization_id' => $organization?->id,
        ]);

        return [
            'firebase_custom_token' => $token,
            'firebase_uid' => $uid,
            'database_url' => config('fishtrace.firebase.database_url'),
            'expires_in' => config('fishtrace.firebase.custom_token_ttl_seconds'),
        ];
    }
}
