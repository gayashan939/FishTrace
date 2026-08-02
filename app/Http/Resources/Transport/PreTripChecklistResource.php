<?php

namespace App\Http\Resources\Transport;

use App\Models\PreTripChecklist;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class PreTripChecklistResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $checklist = $this->model();

        return [
            'id' => $checklist->id,
            'transport_trip_id' => $checklist->transport_trip_id,
            'completed_by' => $checklist->completed_by,
            'completed_at' => $checklist->completed_at,
            'created_at' => $checklist->created_at,
            'updated_at' => $checklist->updated_at,
            'items' => $this->when($checklist->relationLoaded('items'), fn () => ChecklistItemResource::collection($checklist->items)),
        ];
    }

    private function model(): PreTripChecklist
    {
        if (! $this->resource instanceof PreTripChecklist) {
            throw new LogicException('PreTripChecklistResource requires a PreTripChecklist model.');
        }

        return $this->resource;
    }
}
