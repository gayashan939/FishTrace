<?php

namespace App\Actions\Support;

use App\Models\SupportIssue;
use App\Models\User;

class CreateSupportIssue
{
    /** @param array{subject: string, description: string} $data */
    public function execute(User $user, array $data): SupportIssue
    {
        return SupportIssue::query()->create([
            'user_id' => $user->id,
            'organization_id' => $user->primaryOrganization()?->id,
            'subject' => $data['subject'],
            'description' => $data['description'],
            'status' => 'OPEN',
        ]);
    }
}
