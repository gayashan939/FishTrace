<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    use HasUuids;

    protected $fillable = ['message_id', 'iot_device_id', 'transport_trip_id', 'product_temperature', 'air_temperature', 'humidity', 'latitude', 'longitude', 'speed_kph', 'battery_percentage', 'signal_strength', 'door_open', 'recorded_at', 'imported_at', 'raw_payload'];

    protected $hidden = ['raw_payload'];

    protected function casts(): array
    {
        return ['door_open' => 'boolean', 'recorded_at' => 'datetime', 'imported_at' => 'datetime', 'raw_payload' => 'array'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'iot_device_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(TransportTrip::class, 'transport_trip_id');
    }
}
