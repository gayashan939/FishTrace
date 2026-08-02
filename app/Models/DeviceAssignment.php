<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceAssignment extends Model
{
    use HasUuids;

    protected $fillable = ['iot_device_id', 'transport_trip_id', 'status', 'firebase_sync_status', 'firebase_sync_error', 'assigned_at', 'expires_at', 'ended_at'];

    protected $hidden = ['firebase_sync_error'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime', 'expires_at' => 'datetime', 'ended_at' => 'datetime'];
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
