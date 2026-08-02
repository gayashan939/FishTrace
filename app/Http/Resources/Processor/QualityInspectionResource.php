<?php

namespace App\Http\Resources\Processor;

use App\Http\Resources\Fisher\FishBatchResource;
use App\Models\QualityInspection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class QualityInspectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $inspection = $this->model();

        return [
            'id' => $inspection->id,
            'fish_batch_id' => $inspection->fish_batch_id,
            'processing_record_id' => $inspection->processing_record_id,
            'organization_id' => $inspection->organization_id,
            'inspector_id' => $inspection->inspector_id,
            'quality_grade_id' => $inspection->quality_grade_id,
            'result' => $inspection->result,
            'product_temperature' => $inspection->product_temperature,
            'ph_level' => $inspection->ph_level,
            'appearance' => $inspection->appearance,
            'odor' => $inspection->odor,
            'notes' => $inspection->notes,
            'inspected_at' => $inspection->inspected_at,
            'created_at' => $inspection->created_at,
            'updated_at' => $inspection->updated_at,
            'batch' => $this->when($inspection->relationLoaded('batch'), fn () => new FishBatchResource($inspection->batch)),
            'processing_record' => $this->when($inspection->relationLoaded('processingRecord'), fn () => new ProcessingRecordResource($inspection->processingRecord)),
        ];
    }

    private function model(): QualityInspection
    {
        if (! $this->resource instanceof QualityInspection) {
            throw new LogicException('QualityInspectionResource requires a QualityInspection model.');
        }

        return $this->resource;
    }
}
