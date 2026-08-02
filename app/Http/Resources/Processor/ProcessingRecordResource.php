<?php

namespace App\Http\Resources\Processor;

use App\Http\Resources\Fisher\FishBatchResource;
use App\Models\ProcessingRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class ProcessingRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $record = $this->model();

        return [
            'id' => $record->id,
            'fish_batch_id' => $record->fish_batch_id,
            'batch_intake_id' => $record->batch_intake_id,
            'organization_id' => $record->organization_id,
            'created_by' => $record->created_by,
            'processing_type_id' => $record->processing_type_id,
            'status' => $record->status,
            'input_weight_kg' => $record->input_weight_kg,
            'output_weight_kg' => $record->output_weight_kg,
            'waste_weight_kg' => $record->waste_weight_kg,
            'notes' => $record->notes,
            'started_at' => $record->started_at,
            'completed_at' => $record->completed_at,
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at,
            'batch' => $this->when($record->relationLoaded('batch'), fn () => new FishBatchResource($record->batch)),
            'intake' => $this->when($record->relationLoaded('intake'), fn () => new BatchIntakeResource($record->intake)),
            'steps' => $this->when($record->relationLoaded('steps'), fn () => ProcessingStepResource::collection($record->steps)),
            'inspections' => $this->when($record->relationLoaded('inspections'), fn () => QualityInspectionResource::collection($record->inspections)),
        ];
    }

    private function model(): ProcessingRecord
    {
        if (! $this->resource instanceof ProcessingRecord) {
            throw new LogicException('ProcessingRecordResource requires a ProcessingRecord model.');
        }

        return $this->resource;
    }
}
