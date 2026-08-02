<?php

namespace App\Http\Resources\Fisher;

use App\Http\Resources\Processor\BatchIntakeResource;
use App\Models\FishBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class FishBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $batch = $this->model();

        return [
            'id' => $batch->id,
            'organization_id' => $batch->organization_id,
            'created_by' => $batch->created_by,
            'fish_species_id' => $batch->fish_species_id,
            'batch_code' => $batch->batch_code,
            'type' => $batch->type,
            'status' => $batch->status,
            'product_type' => $batch->product_type,
            'total_weight_kg' => $batch->total_weight_kg,
            'created_from_catch_at' => $batch->created_from_catch_at,
            'is_public' => $batch->is_public,
            'is_recalled' => $batch->is_recalled,
            'created_at' => $batch->created_at,
            'updated_at' => $batch->updated_at,
            'species' => $this->when($batch->relationLoaded('species'), fn () => $batch->species?->only(['id', 'common_name', 'scientific_name', 'is_active'])),
            'catches' => $this->when($batch->relationLoaded('catches'), fn () => CatchRecordResource::collection($batch->catches)),
            'qr_code' => $this->when($batch->relationLoaded('qrCode'), fn () => $batch->qrCode?->only(['id', 'fish_batch_id', 'revoked_at', 'created_at', 'updated_at'])),
            'events' => $this->when($batch->relationLoaded('events'), fn () => TraceabilityEventResource::collection($batch->events)),
            'intakes' => $this->when($batch->relationLoaded('intakes'), fn () => BatchIntakeResource::collection($batch->intakes)),
        ];
    }

    private function model(): FishBatch
    {
        if (! $this->resource instanceof FishBatch) {
            throw new LogicException('FishBatchResource requires a FishBatch model.');
        }

        return $this->resource;
    }
}
