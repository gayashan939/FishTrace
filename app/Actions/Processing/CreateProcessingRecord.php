<?php

namespace App\Actions\Processing;

use App\Enums\BatchStatus;
use App\Enums\ProcessingStatus;
use App\Enums\ProcessingStepType;
use App\Models\BatchIntake;
use App\Models\FishBatch;
use App\Models\ProcessingRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateProcessingRecord
{
    public function execute(User $user, array $data): ProcessingRecord
    {
        $organization = $user->primaryOrganization();
        abort_unless($organization !== null, 403, 'A processor organization is required.');

        return DB::transaction(function () use ($user, $organization, $data): ProcessingRecord {
            $batch = FishBatch::query()->lockForUpdate()->findOrFail($data['fish_batch_id']);
            abort_unless($batch->getRawOriginal('status') === BatchStatus::ACCEPTED_BY_PROCESSOR->value, 409, 'The batch is not ready for processing.');
            $intake = BatchIntake::query()->where('fish_batch_id', $batch->id)->where('processor_organization_id', $organization->id)->where('status', 'ACCEPTED')->first();
            abort_unless($intake !== null, 403, 'This batch is not assigned to your processor organization.');
            $maximum = min((float) $batch->total_weight_kg, (float) $intake->received_weight_kg);
            abort_if((float) $data['input_weight_kg'] > $maximum, 422, "Input weight exceeds the received {$maximum} kg.");
            abort_if(ProcessingRecord::query()->where('fish_batch_id', $batch->id)->exists(), 409, 'A processing record already exists for this batch.');
            $record = ProcessingRecord::create(['fish_batch_id' => $batch->id, 'batch_intake_id' => $intake->id, 'organization_id' => $organization->id, 'created_by' => $user->id, 'processing_type_id' => $data['processing_type_id'] ?? null, 'status' => ProcessingStatus::IN_PROGRESS, 'input_weight_kg' => $data['input_weight_kg'], 'notes' => $data['notes'] ?? null, 'started_at' => now()]);
            foreach (ProcessingStepType::cases() as $step) {
                $record->steps()->create(['type' => $step, 'sequence' => $step->sequence(), 'status' => 'PENDING']);
            }
            $batch->update(['status' => BatchStatus::PROCESSING]);
            $batch->events()->create(['organization_id' => $organization->id, 'actor_id' => $user->id, 'event_type' => 'PROCESSING_STARTED', 'title' => 'Processing started', 'public_data' => ['input_weight_kg' => (float) $data['input_weight_kg']], 'occurred_at' => now()]);

            return $record->load(['batch.species', 'steps']);
        });
    }
}
