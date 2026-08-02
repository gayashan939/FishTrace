<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IotDevice extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'device_code', 'serial_number', 'display_name', 'status', 'firmware_version', 'battery_percentage', 'signal_strength', 'last_seen_at', 'firebase_uid', 'firebase_email', 'firebase_auth_enabled', 'credential_version', 'last_token_refresh_at', 'supports_product_temperature', 'supports_air_temperature', 'supports_humidity', 'supports_gps', 'supports_door_sensor'];

    protected $hidden = ['firebase_uid', 'firebase_email'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime', 'last_token_refresh_at' => 'datetime', 'firebase_auth_enabled' => 'boolean'];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DeviceAssignment::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function syncCursor(): HasOne
    {
        return $this->hasOne(FirebaseSyncCursor::class, 'device_id');
    }

    public function syncFailures(): HasMany
    {
        return $this->hasMany(FirebaseSyncFailure::class, 'device_id');
    }
}
