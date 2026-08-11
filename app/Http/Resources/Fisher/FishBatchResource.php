<?php

namespace App\Http\Resources\Fisher;

use App\Http\Resources\Files\FileAssetResource;
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
            'fishing_trip_id' => $batch->fishing_trip_id,
            'batch_code' => $batch->batch_code,
            'type' => $batch->type,
            'status' => $batch->status,
            'product_type' => $batch->product_type,
            'total_weight_kg' => $batch->total_weight_kg,
            'fish_count' => $batch->fish_count,
            'quality_grade' => $batch->quality_grade,
            'storage_temperature_celsius' => $batch->storage_temperature_celsius,
            'ice_type' => $batch->ice_type,
            'ice_amount_kg' => $batch->ice_amount_kg,
            'landing_site_name' => $batch->landing_site_name,
            'notes' => $batch->notes,
            'created_from_catch_at' => $batch->created_from_catch_at,
            'is_public' => $batch->is_public,
            'is_recalled' => $batch->is_recalled,
            'created_at' => $batch->created_at,
            'updated_at' => $batch->updated_at,
            'species' => $this->when($batch->relationLoaded('species'), fn () => $batch->species?->only(['id', 'common_name', 'scientific_name', 'is_active'])),
            'organization' => $this->when($batch->relationLoaded('organization'), fn () => $batch->organization?->only(['id', 'name', 'code'])),
            'catches' => $this->when($batch->relationLoaded('catches'), fn () => CatchRecordResource::collection($batch->catches)),
            'qr_code' => $this->when($batch->relationLoaded('qrCode'), function () use ($batch) {
                if (! $batch->qrCode) {
                    return null;
                }

                return [
                    ...$batch->qrCode->only(['id', 'fish_batch_id', 'revoked_at', 'created_at', 'updated_at']),
                    'trace_url' => url('/trace/'.$batch->qrCode->getRawOriginal('public_token')),
                ];
            }),
            'trip' => $this->when($batch->relationLoaded('fishingTrip'), fn () => $batch->fishingTrip ? new FishingTripResource($batch->fishingTrip) : null),
            'events' => $this->when($batch->relationLoaded('events'), fn () => TraceabilityEventResource::collection($batch->events)),
            'intakes' => $this->when($batch->relationLoaded('intakes'), fn () => BatchIntakeResource::collection($batch->intakes)),
            'documents' => $this->when($batch->relationLoaded('documents'), fn () => FileAssetResource::collection($batch->documents)),
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
