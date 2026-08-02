<?php

namespace App\Actions\Processing;

use App\Enums\BatchStatus;
use App\Enums\NotificationType;
use App\Models\BatchIntake;
use App\Models\FishBatch;
use App\Models\User;
use App\Services\Notifications\OperationalNotifier;
use Illuminate\Support\Facades\DB;

class ReceiveBatch
{
    public function __construct(private OperationalNotifier $notifier) {}

    public function accept(User $user, FishBatch $batch, array $data): BatchIntake
    {
        return DB::transaction(function () use ($user, $batch, $data): BatchIntake {
            $locked = FishBatch::query()->lockForUpdate()->findOrFail($batch->id);
            abort_unless($locked->getRawOriginal('status') === BatchStatus::AVAILABLE_FOR_PROCESSING->value, 409, 'Only an available batch can be accepted.');
            $organization = $user->primaryOrganization();
            abort_unless($organization !== null, 403, 'A processor organization is required.');
            abort_if(BatchIntake::query()->where('fish_batch_id', $locked->id)->where('status', 'ACCEPTED')->exists(), 409, 'This batch has already been accepted.');
            $intake = BatchIntake::updateOrCreate(['fish_batch_id' => $locked->id, 'processor_organization_id' => $organization->id], ['received_by' => $user->id, 'status' => 'ACCEPTED', 'received_weight_kg' => $data['received_weight_kg'], 'rejection_reason' => null, 'notes' => $data['notes'] ?? null, 'received_at' => now()]);
            $locked->update(['status' => BatchStatus::ACCEPTED_BY_PROCESSOR]);
            $locked->events()->create(['organization_id' => $organization->id, 'actor_id' => $user->id, 'event_type' => 'PROCESSOR_ACCEPTED', 'title' => 'Batch accepted by processor', 'public_data' => ['received_weight_kg' => (float) $data['received_weight_kg'], 'facility' => $organization->name], 'occurred_at' => now()]);
            $this->notifier->organization($locked->organization_id, NotificationType::BATCH_ACCEPTED, 'Batch accepted', $locked->batch_code.' was accepted by '.$organization->name.'.', ['batch_id' => $locked->id, 'intake_id' => $intake->id]);

            return $intake->load('batch.species');
        });
    }

    public function reject(User $user, FishBatch $batch, array $data): BatchIntake
    {
        return DB::transaction(function () use ($user, $batch, $data): BatchIntake {
            $locked = FishBatch::query()->lockForUpdate()->findOrFail($batch->id);
            abort_unless($locked->getRawOriginal('status') === BatchStatus::AVAILABLE_FOR_PROCESSING->value, 409, 'Only an available batch can be rejected.');
            $organization = $user->primaryOrganization();
            abort_unless($organization !== null, 403, 'A processor organization is required.');
            $intake = BatchIntake::updateOrCreate(['fish_batch_id' => $locked->id, 'processor_organization_id' => $organization->id], ['received_by' => $user->id, 'status' => 'REJECTED', 'received_weight_kg' => $data['received_weight_kg'] ?? null, 'rejection_reason' => $data['rejection_reason'], 'notes' => $data['notes'] ?? null, 'received_at' => now()]);
            $locked->events()->create(['organization_id' => $organization->id, 'actor_id' => $user->id, 'event_type' => 'PROCESSOR_REJECTED', 'title' => 'Batch rejected by processor', 'public_data' => ['reason' => $data['rejection_reason']], 'occurred_at' => now()]);
            $this->notifier->organization($locked->organization_id, NotificationType::BATCH_REJECTED, 'Batch rejected', $locked->batch_code.' was rejected by '.$organization->name.'.', ['batch_id' => $locked->id, 'reason' => $data['rejection_reason']]);

            return $intake->load('batch.species');
        });
    }
}
