<?php

namespace App\Services\Processing;

use App\Enums\BatchStatus;
use App\Models\BatchIntake;
use App\Models\FishBatch;
use App\Models\ProcessingRecord;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

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
            ->with(['species', 'qrCode', 'organization:id,name,code', 'fishingTrip.boat:id,name,registration_number', 'documents'])
            ->where('status', BatchStatus::AVAILABLE_FOR_PROCESSING)
            ->latest()
            ->paginate($perPage);
    }

    public function resolveIncoming(string $scannedValue): ?FishBatch
    {
        $value = trim($scannedValue);
        $path = parse_url($value, PHP_URL_PATH);
        $token = is_string($path) ? basename(trim($path, '/')) : $value;

        return FishBatch::query()
            ->with(['species', 'qrCode'])
            ->where('status', BatchStatus::AVAILABLE_FOR_PROCESSING)
            ->where(function ($query) use ($value, $token): void {
                if (Str::isUuid($value)) {
                    $query->orWhereKey($value);
                }
                if (Str::isUuid($token)) {
                    $query->orWhereKey($token);
                }
                $query
                    ->orWhere('batch_code', $value)
                    ->orWhere('batch_code', $token)
                    ->orWhereHas('qrCode', fn ($qr) => $qr->where('public_token', $token)->whereNull('revoked_at'));
            })
            ->first();
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
        return $batch->load(['species', 'qrCode', 'organization:id,name,code', 'fishingTrip.boat:id,name,registration_number', 'documents', 'events', 'intakes.processingRecord.steps']);
    }
}
