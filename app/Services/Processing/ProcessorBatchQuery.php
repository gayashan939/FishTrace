<?php

namespace App\Services\Processing;

use App\Enums\BatchStatus;
use App\Models\BatchIntake;
use App\Models\FishBatch;
use App\Models\ProcessingRecord;
use Illuminate\Pagination\LengthAwarePaginator;

class ProcessorBatchQuery
{
    public function dashboard(?string $organizationId): array
    {
        return [
            'incoming' => FishBatch::query()->where('status', BatchStatus::AVAILABLE_FOR_PROCESSING)->count(),
            'accepted' => BatchIntake::query()->where('processor_organization_id', $organizationId)->where('status', 'ACCEPTED')->count(),
            'processing' => ProcessingRecord::query()->where('organization_id', $organizationId)->where('status', 'IN_PROGRESS')->count(),
            'quality_holds' => ProcessingRecord::query()->where('organization_id', $organizationId)->where('status', 'QUALITY_HOLD')->count(),
        ];
    }

    public function incoming(int $perPage): LengthAwarePaginator
    {
        return FishBatch::query()
            ->with(['species', 'qrCode'])
            ->where('status', BatchStatus::AVAILABLE_FOR_PROCESSING)
            ->latest()
            ->paginate($perPage);
    }

    public function history(?string $organizationId, int $perPage): LengthAwarePaginator
    {
        return BatchIntake::query()
            ->with(['batch.species', 'processingRecord.steps'])
            ->where('processor_organization_id', $organizationId)
            ->latest('received_at')
            ->paginate($perPage);
    }

    public function details(FishBatch $batch): FishBatch
    {
        return $batch->load(['species', 'qrCode', 'events', 'intakes.processingRecord.steps']);
    }
}
