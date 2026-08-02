<?php

namespace App\Policies;

use App\Models\ProcessingRecord;
use App\Models\ProcessingStep;
use App\Models\User;

class ProcessingStepPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function view(User $user, ProcessingStep $step): bool
    {
        $record = $step->processingRecord;

        return $record instanceof ProcessingRecord
            && ($user->hasRole('PROCESSOR') || $user->hasRole('INSPECTOR'))
            && $user->primaryOrganization()?->id === $record->organization_id;
    }
}
