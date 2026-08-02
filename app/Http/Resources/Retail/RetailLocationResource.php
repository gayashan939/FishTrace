<?php

namespace App\Http\Resources\Retail;

use App\Models\RetailLocation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class RetailLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $location = $this->model();

        return [
            'id' => $location->id,
            'organization_id' => $location->organization_id,
            'code' => $location->code,
            'name' => $location->name,
            'address' => $location->address,
            'is_active' => $location->is_active,
            'created_at' => $location->created_at,
            'updated_at' => $location->updated_at,
        ];
    }

    private function model(): RetailLocation
    {
        if (! $this->resource instanceof RetailLocation) {
            throw new LogicException('RetailLocationResource requires a RetailLocation model.');
        }

        return $this->resource;
    }
}
