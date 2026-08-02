<?php

namespace App\Http\Resources\Transport;

use App\Models\TransportIncident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class TransportIncidentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $incident = $this->model();

        return [
            'id' => $incident->id,
            'transport_trip_id' => $incident->transport_trip_id,
            'reported_by' => $incident->reported_by,
            'type' => $incident->type,
            'severity' => $incident->severity,
            'description' => $incident->description,
            'occurred_at' => $incident->occurred_at,
            'created_at' => $incident->created_at,
            'updated_at' => $incident->updated_at,
        ];
    }

    private function model(): TransportIncident
    {
        if (! $this->resource instanceof TransportIncident) {
            throw new LogicException('TransportIncidentResource requires a TransportIncident model.');
        }

        return $this->resource;
    }
}
