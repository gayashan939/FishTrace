<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use BelongsToOrganization, HasUuids, SoftDeletes;

    protected $fillable = ['organization_id', 'registration_number', 'name', 'vehicle_type', 'refrigeration_category', 'capacity_tonnes', 'reefer_unit', 'min_temperature_celsius', 'max_temperature_celsius', 'default_driver_name', 'is_active'];

    protected function casts(): array
    {
        return ['capacity_tonnes' => 'decimal:3', 'min_temperature_celsius' => 'decimal:2', 'max_temperature_celsius' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function trips(): HasMany
    {
        return $this->hasMany(TransportTrip::class);
    }
}
