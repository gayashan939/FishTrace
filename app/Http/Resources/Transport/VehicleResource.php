<?php

namespace App\Http\Resources\Transport;

use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $vehicle = $this->model();

        return [
            'id' => $vehicle->id,
            'organization_id' => $vehicle->organization_id,
            'registration_number' => $vehicle->registration_number,
            'name' => $vehicle->name,
            'is_active' => $vehicle->is_active,
            'trips_count' => $this->when($vehicle->getAttribute('trips_count') !== null, $vehicle->getAttribute('trips_count')),
            'created_at' => $vehicle->created_at,
            'updated_at' => $vehicle->updated_at,
        ];
    }

    private function model(): Vehicle
    {
        if (! $this->resource instanceof Vehicle) {
            throw new LogicException('VehicleResource requires a Vehicle model.');
        }

        return $this->resource;
    }
}
