<?php

namespace App\Models;

use App\Enums\TransportTripStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransportTrip extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'created_by', 'vehicle_id', 'trip_code', 'driver_name', 'status', 'origin', 'destination', 'scheduled_at', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return ['status' => TransportTripStatus::class, 'scheduled_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(FishBatch::class, 'transport_batches');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(DeviceAssignment::class);
    }

    public function activeAssignment(): HasOne
    {
        return $this->hasOne(DeviceAssignment::class)->where('status', 'ACTIVE')->latestOfMany();
    }

    public function readings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(ColdChainAlert::class);
    }

    public function checklist(): HasOne
    {
        return $this->hasOne(PreTripChecklist::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(TransportIncident::class);
    }

    public function deliveryConfirmation(): HasOne
    {
        return $this->hasOne(DeliveryConfirmation::class);
    }

    public function hasStatus(TransportTripStatus $status): bool
    {
        return $this->getRawOriginal('status') === $status->value;
    }
}
