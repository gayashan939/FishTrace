<?php

namespace App\Http\Resources\IoT;

use App\Models\IotDevice;
use App\Models\SensorReading;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class SensorReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reading = $this->resource;
        if (! $reading instanceof SensorReading) {
            throw new LogicException('SensorReadingResource requires a SensorReading model.');
        }
        $recordedAt = $reading->getAttribute('recorded_at');
        $device = $reading->relationLoaded('device') ? $reading->getRelation('device') : null;

        return [
            'id' => $reading->id,
            'message_id' => $reading->message_id,
            'iot_device_id' => $reading->iot_device_id,
            'transport_trip_id' => $reading->transport_trip_id,
            'product_temperature' => $reading->product_temperature,
            'air_temperature' => $reading->air_temperature,
            'humidity' => $reading->humidity,
            'latitude' => $reading->latitude,
            'longitude' => $reading->longitude,
            'speed_kph' => $reading->speed_kph,
            'battery_percentage' => $reading->battery_percentage,
            'signal_strength' => $reading->signal_strength,
            'door_open' => $reading->door_open,
            'recorded_at' => $recordedAt,
            'imported_at' => $reading->getAttribute('imported_at'),
            'reading_age_seconds' => $recordedAt instanceof CarbonInterface ? max(0, (int) $recordedAt->diffInSeconds(now(), true)) : null,
            'device_status' => $device instanceof IotDevice ? $device->status : null,
        ];
    }
}
