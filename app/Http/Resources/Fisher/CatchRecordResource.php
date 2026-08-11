<?php

namespace App\Http\Resources\Fisher;

use App\Http\Resources\Files\FileAssetResource;
use App\Models\CatchRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class CatchRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $catch = $this->model();

        return [
            'id' => $catch->id,
            'organization_id' => $catch->organization_id,
            'fishing_trip_id' => $catch->fishing_trip_id,
            'fish_species_id' => $catch->fish_species_id,
            'fishing_gear_type_id' => $catch->fishing_gear_type_id,
            'client_record_id' => $catch->client_record_id,
            'weight_kg' => $catch->weight_kg,
            'quantity' => $catch->quantity,
            'condition' => $catch->condition,
            'latitude' => $catch->latitude,
            'longitude' => $catch->longitude,
            'notes' => $catch->notes,
            'verified' => true,
            'allocated_weight_kg' => $catch->allocated_weight_kg,
            'caught_at' => $catch->caught_at,
            'client_created_at' => $catch->client_created_at,
            'created_at' => $catch->created_at,
            'updated_at' => $catch->updated_at,
            'trip' => $this->when($catch->relationLoaded('trip'), fn () => new FishingTripResource($catch->trip)),
            'species' => $this->when($catch->relationLoaded('species'), fn () => $catch->species?->only(['id', 'common_name', 'scientific_name', 'is_active'])),
            'gear' => $this->when($catch->relationLoaded('gearType'), fn () => $catch->gearType?->only(['id', 'name', 'is_active'])),
            'images' => $this->when($catch->relationLoaded('images'), fn () => FileAssetResource::collection($catch->images)),
            'batches' => $this->when($catch->relationLoaded('batches'), fn () => FishBatchResource::collection($catch->batches)),
        ];
    }

    private function model(): CatchRecord
    {
        if (! $this->resource instanceof CatchRecord) {
            throw new LogicException('CatchRecordResource requires a CatchRecord model.');
        }

        return $this->resource;
    }
}
