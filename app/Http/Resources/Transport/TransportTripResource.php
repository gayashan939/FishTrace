<?php

namespace App\Http\Resources\Transport;

use App\Http\Resources\Fisher\FishBatchResource;
use App\Models\SensorReading;
use App\Models\TransportTrip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class TransportTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $trip = $this->model();

        return [
            'id' => $trip->id,
            'organization_id' => $trip->organization_id,
            'created_by' => $trip->created_by,
            'vehicle_id' => $trip->vehicle_id,
            'trip_code' => $trip->trip_code,
            'driver_name' => $trip->driver_name,
            'status' => $trip->status,
            'origin' => $trip->origin,
            'destination' => $trip->destination,
            'estimated_distance_km' => $trip->estimated_distance_km,
            'product_temperature_celsius' => $this->when(
                $trip->relationLoaded('latestReading'),
                fn () => $this->productTemperature($trip),
            ),
            'scheduled_at' => $trip->scheduled_at,
            'started_at' => $trip->started_at,
            'arrived_at' => $trip->arrived_at,
            'completed_at' => $trip->completed_at,
            'created_at' => $trip->created_at,
            'updated_at' => $trip->updated_at,
            'vehicle' => $this->when($trip->relationLoaded('vehicle'), fn () => new VehicleResource($trip->vehicle)),
            'batches' => $this->when($trip->relationLoaded('batches'), fn () => FishBatchResource::collection($trip->batches)),
            'active_assignment' => $this->when($trip->relationLoaded('activeAssignment'), fn () => $trip->activeAssignment === null ? null : new DeviceAssignmentResource($trip->activeAssignment)),
            'checklist' => $this->when($trip->relationLoaded('checklist'), fn () => $trip->checklist === null ? null : new PreTripChecklistResource($trip->checklist)),
            'incidents' => $this->when($trip->relationLoaded('incidents'), fn () => TransportIncidentResource::collection($trip->incidents)),
            'delivery_confirmation' => $this->when($trip->relationLoaded('deliveryConfirmation'), fn () => $trip->deliveryConfirmation === null ? null : new DeliveryConfirmationResource($trip->deliveryConfirmation)),
        ];
    }

    private function model(): TransportTrip
    {
        if (! $this->resource instanceof TransportTrip) {
            throw new LogicException('TransportTripResource requires a TransportTrip model.');
        }

        return $this->resource;
    }

    private function productTemperature(TransportTrip $trip): mixed
    {
        $reading = $trip->getRelation('latestReading');

        return $reading instanceof SensorReading ? $reading->product_temperature : null;
    }
}
