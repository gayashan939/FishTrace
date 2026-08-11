<?php

namespace App\Http\Resources\Fisher;

use App\Models\Boat;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class BoatResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $boat = $this->model();

        return [
            'id' => $boat->id,
            'organization_id' => $boat->organization_id,
            'owner_id' => $boat->owner_id,
            'registration_number' => $boat->registration_number,
            'name' => $boat->name,
            'type' => $boat->type,
            'capacity_kg' => $boat->capacity_kg,
            'length_meters' => $boat->length_meters,
            'engine_details' => $boat->engine_details,
            'home_port' => $boat->home_port,
            'is_active' => $boat->is_active,
            'created_at' => $boat->created_at,
            'updated_at' => $boat->updated_at,
        ];
    }

    private function model(): Boat
    {
        if (! $this->resource instanceof Boat) {
            throw new LogicException('BoatResource requires a Boat model.');
        }

        return $this->resource;
    }
}
