<?php

namespace App\Http\Resources\Processor;

use App\Http\Resources\Fisher\FishBatchResource;
use App\Models\BatchIntake;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class BatchIntakeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $intake = $this->model();

        return [
            'id' => $intake->id,
            'fish_batch_id' => $intake->fish_batch_id,
            'processor_organization_id' => $intake->processor_organization_id,
            'received_by' => $intake->received_by,
            'status' => $intake->status,
            'received_weight_kg' => $intake->received_weight_kg,
            'rejection_reason' => $intake->rejection_reason,
            'notes' => $intake->notes,
            'received_at' => $intake->received_at,
            'created_at' => $intake->created_at,
            'updated_at' => $intake->updated_at,
            'batch' => $this->when($intake->relationLoaded('batch'), fn () => new FishBatchResource($intake->batch)),
            'processing_record' => $this->when($intake->relationLoaded('processingRecord'), fn () => $intake->processingRecord === null ? null : new ProcessingRecordResource($intake->processingRecord)),
        ];
    }

    private function model(): BatchIntake
    {
        if (! $this->resource instanceof BatchIntake) {
            throw new LogicException('BatchIntakeResource requires a BatchIntake model.');
        }

        return $this->resource;
    }
}
