<?php

namespace App\Policies;

use App\Enums\BatchStatus;
use App\Models\BatchIntake;
use App\Models\FishBatch;
use App\Models\User;

class FishBatchPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->roles->isNotEmpty();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('FISHER');
    }

    public function view(User $user, FishBatch $batch): bool
    {
        if ($user->primaryOrganization()?->id === $batch->organization_id) {
            return true;
        }
        if ($user->hasRole('PROCESSOR')) {
            return $batch->getRawOriginal('status') === BatchStatus::AVAILABLE_FOR_PROCESSING->value
                || BatchIntake::query()->where('fish_batch_id', $batch->id)->where('processor_organization_id', $user->primaryOrganization()?->id)->exists();
        }

        return $user->hasRole('INSPECTOR');
    }

    public function receive(User $user, FishBatch $batch): bool
    {
        return $user->hasRole('PROCESSOR') && $batch->getRawOriginal('status') === BatchStatus::AVAILABLE_FOR_PROCESSING->value;
    }

    public function attachDocument(User $user, FishBatch $batch): bool
    {
        return $user->hasRole('FISHER') && $user->primaryOrganization()?->id === $batch->organization_id;
    }

    public function process(User $user, FishBatch $batch): bool
    {
        return $user->hasRole('PROCESSOR') && BatchIntake::query()->where('fish_batch_id', $batch->id)->where('processor_organization_id', $user->primaryOrganization()?->id)->where('status', 'ACCEPTED')->exists();
    }

    public function split(User $user, FishBatch $batch): bool
    {
        return $this->process($user, $batch) && in_array($batch->getRawOriginal('status'), [BatchStatus::PROCESSED->value, BatchStatus::READY_FOR_TRANSPORT->value], true);
    }
}
