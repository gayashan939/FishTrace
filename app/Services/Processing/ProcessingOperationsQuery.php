<?php

namespace App\Services\Processing;

use App\Models\AIPrediction;
use App\Models\FishBatch;
use App\Models\PackageLabel;
use App\Models\ProcessingRecord;
use App\Models\QualityInspection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProcessingOperationsQuery
{
    public function record(ProcessingRecord $record): ProcessingRecord
    {
        return $record->load(['batch.species', 'intake', 'steps', 'inspections']);
    }

    public function inspection(QualityInspection $inspection): QualityInspection
    {
        return $inspection->load(['batch.species', 'processingRecord']);
    }

    public function label(PackageLabel $label): PackageLabel
    {
        return $label->load('batch.species');
    }

    public function children(FishBatch $batch, int $perPage): LengthAwarePaginator
    {
        $ids = $batch->childLinks()->pluck('child_batch_id');

        return FishBatch::query()->with(['species', 'qrCode'])->whereIn('id', $ids)->paginate($perPage);
    }

    public function predictions(FishBatch $batch, int $perPage): LengthAwarePaginator
    {
        return AIPrediction::query()->where('fish_batch_id', $batch->id)->latest('predicted_at')->paginate($perPage);
    }

    public function latestPrediction(FishBatch $batch): ?AIPrediction
    {
        return AIPrediction::query()->where('fish_batch_id', $batch->id)->latest('predicted_at')->first();
    }
}
