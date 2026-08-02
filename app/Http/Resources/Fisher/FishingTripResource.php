<?php

namespace App\Http\Resources\Fisher;

use App\Models\FishingTrip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class FishingTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $trip = $this->model();

        return [
            'id' => $trip->id,
            'organization_id' => $trip->organization_id,
            'fisher_id' => $trip->fisher_id,
            'boat_id' => $trip->boat_id,
            'landing_site_id' => $trip->landing_site_id,
            'trip_code' => $trip->trip_code,
            'status' => $trip->status,
            'general_catch_area' => $trip->general_catch_area,
            'departed_at' => $trip->departed_at,
            'returned_at' => $trip->returned_at,
            'created_at' => $trip->created_at,
            'updated_at' => $trip->updated_at,
            'boat' => $this->when($trip->relationLoaded('boat'), fn () => new BoatResource($trip->boat)),
            'catches' => $this->when($trip->relationLoaded('catches'), fn () => CatchRecordResource::collection($trip->catches)),
        ];
    }

    private function model(): FishingTrip
    {
        if (! $this->resource instanceof FishingTrip) {
            throw new LogicException('FishingTripResource requires a FishingTrip model.');
        }

        return $this->resource;
    }
}
