<?php

namespace App\Actions\Processing;

use App\Enums\ProcessingStatus;
use App\Enums\ProcessingStepType;
use App\Models\ProcessingRecord;
use App\Models\ProcessingStep;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TransitionProcessingStep
{
    public function start(User $user, ProcessingRecord $record, ProcessingStep $step): ProcessingStep
    {
        return DB::transaction(function () use ($user, $record, $step): ProcessingStep {
            $lockedRecord = ProcessingRecord::query()->lockForUpdate()->findOrFail($record->id);
            $locked = ProcessingStep::query()->lockForUpdate()->findOrFail($step->id);
            abort_unless($locked->processing_record_id === $lockedRecord->id, 404);
            abort_unless($lockedRecord->hasStatus(ProcessingStatus::IN_PROGRESS), 409, 'Processing is not active.');
            abort_unless($locked->status === 'PENDING', 409, 'Only a pending step can be started.');
            abort_if(ProcessingStep::query()->where('processing_record_id', $lockedRecord->id)->where('sequence', '<', $locked->sequence)->where('status', '!=', 'COMPLETED')->exists(), 409, 'Complete prior processing steps first.');
            $locked->update(['status' => 'ACTIVE', 'performed_by' => $user->id, 'started_at' => now()]);

            return $locked->fresh();
        });
    }

    public function complete(User $user, ProcessingRecord $record, ProcessingStep $step, array $data): ProcessingStep
    {
        return DB::transaction(function () use ($user, $record, $step, $data): ProcessingStep {
            $lockedRecord = ProcessingRecord::query()->lockForUpdate()->findOrFail($record->id);
            $locked = ProcessingStep::query()->lockForUpdate()->findOrFail($step->id);
            abort_unless($locked->processing_record_id === $lockedRecord->id, 404);
            abort_unless($lockedRecord->hasStatus(ProcessingStatus::IN_PROGRESS), 409, 'Processing is not active.');
            abort_unless($locked->status === 'ACTIVE', 409, 'Start this processing step before completing it.');
            $type = ProcessingStepType::from($locked->getRawOriginal('type'));
            $rules = match ($type) {
                ProcessingStepType::CLEANING => ['cleaned_weight_kg' => ['required', 'numeric', 'gt:0']],
                ProcessingStepType::GRADING => ['grade' => ['required', 'string', 'max:50']],
                ProcessingStepType::FREEZING => ['product_temperature' => ['required', 'numeric', 'between:-40,4']],
                ProcessingStepType::PACKAGING => ['output_weight_kg' => ['required', 'numeric', 'gt:0'], 'waste_weight_kg' => ['required', 'numeric', 'min:0'], 'package_count' => ['required', 'integer', 'min:1']],
            };
            $measurements = Validator::make($data['measurements'], $rules)->validate();
            if ($type === ProcessingStepType::PACKAGING) {
                $reconciled = (float) $measurements['output_weight_kg'] + (float) $measurements['waste_weight_kg'];
                abort_if($reconciled > (float) $lockedRecord->input_weight_kg + 0.05, 422, 'Packaged and waste weight exceeds processing input tolerance.');
                $lockedRecord->update(['output_weight_kg' => $measurements['output_weight_kg'], 'waste_weight_kg' => $measurements['waste_weight_kg']]);
            }
            $locked->update(['status' => 'COMPLETED', 'measurements' => $measurements, 'notes' => $data['notes'] ?? null, 'performed_by' => $user->id, 'completed_at' => now()]);

            return $locked->fresh();
        });
    }
}
