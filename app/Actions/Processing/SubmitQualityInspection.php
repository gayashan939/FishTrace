<?php

namespace App\Actions\Processing;

use App\Enums\BatchStatus;
use App\Enums\InspectionResult;
use App\Enums\NotificationType;
use App\Enums\ProcessingStatus;
use App\Models\FishBatch;
use App\Models\ProcessingRecord;
use App\Models\QualityInspection;
use App\Models\User;
use App\Services\Notifications\OperationalNotifier;
use Illuminate\Support\Facades\DB;

class SubmitQualityInspection
{
    public function __construct(private OperationalNotifier $notifier) {}

    public function execute(User $user, array $data): QualityInspection
    {
        $record = ProcessingRecord::query()->findOrFail($data['processing_record_id']);
        if (! $user->hasRole('INSPECTOR')) {
            abort_unless($user->primaryOrganization()?->id === $record->organization_id, 403);
        }
        abort_unless($record->hasStatus(ProcessingStatus::IN_PROGRESS) || $record->hasStatus(ProcessingStatus::QUALITY_HOLD), 409, 'This processing record cannot be inspected.');
        abort_if($record->steps()->where('status', '!=', 'COMPLETED')->exists(), 409, 'All processing steps must be completed before inspection.');

        return DB::transaction(function () use ($user, $record, $data): QualityInspection {
            $batch = FishBatch::query()->lockForUpdate()->findOrFail($record->fish_batch_id);
            $inspection = QualityInspection::create(array_merge($data, ['fish_batch_id' => $batch->id, 'organization_id' => $record->organization_id, 'inspector_id' => $user->id, 'inspected_at' => now()]));
            $result = InspectionResult::from($data['result']);
            if ($result === InspectionResult::PASSED) {
                $record->update(['status' => ProcessingStatus::COMPLETED, 'completed_at' => now()]);
                $batch->update(['status' => BatchStatus::PROCESSED, 'total_weight_kg' => $record->output_weight_kg]);
            } else {
                $record->update(['status' => ProcessingStatus::QUALITY_HOLD]);
            }
            $batch->events()->create(['organization_id' => $record->organization_id, 'actor_id' => $user->id, 'event_type' => 'QUALITY_INSPECTION_COMPLETED', 'title' => 'Quality inspection completed', 'public_data' => ['result' => $result->value, 'product_temperature' => (float) $data['product_temperature']], 'occurred_at' => now()]);
            if ($result === InspectionResult::PASSED) {
                $batch->events()->create(['organization_id' => $record->organization_id, 'actor_id' => $user->id, 'event_type' => 'PROCESSING_COMPLETED', 'title' => 'Processing completed', 'public_data' => ['output_weight_kg' => (float) $record->output_weight_kg], 'occurred_at' => now()]);
                $this->notifier->organization($record->organization_id, NotificationType::PROCESSING_COMPLETED, 'Processing completed', $batch->batch_code.' passed inspection and processing is complete.', ['batch_id' => $batch->id, 'processing_record_id' => $record->id]);
            } else {
                $this->notifier->organization($record->organization_id, NotificationType::INSPECTION_FAILED, 'Inspection requires action', $batch->batch_code.' did not pass quality inspection.', ['batch_id' => $batch->id, 'inspection_id' => $inspection->id, 'result' => $result->value]);
            }

            return $inspection;
        });
    }
}
