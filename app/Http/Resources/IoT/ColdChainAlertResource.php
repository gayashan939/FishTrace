<?php

namespace App\Http\Resources\IoT;

use App\Models\ColdChainAlert;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class ColdChainAlertResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $alert = $this->model();

        return [
            'id' => $alert->id,
            'transport_trip_id' => $alert->transport_trip_id,
            'fish_batch_id' => $alert->fish_batch_id,
            'type' => $alert->type,
            'severity' => $alert->severity,
            'status' => $alert->status,
            'measured_value' => $alert->measured_value,
            'threshold_value' => $alert->threshold_value,
            'first_detected_at' => $alert->first_detected_at,
            'last_detected_at' => $alert->last_detected_at,
            'resolved_at' => $alert->resolved_at,
            'created_at' => $alert->created_at,
            'updated_at' => $alert->updated_at,
        ];
    }

    private function model(): ColdChainAlert
    {
        if (! $this->resource instanceof ColdChainAlert) {
            throw new LogicException('ColdChainAlertResource requires a ColdChainAlert model.');
        }

        return $this->resource;
    }
}
