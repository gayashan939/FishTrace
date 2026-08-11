<?php

namespace App\Models;

use App\Enums\FishingTripStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FishingTrip extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'fisher_id', 'boat_id', 'landing_site_id', 'trip_code', 'client_record_id', 'status', 'general_catch_area', 'planned_departure_at', 'expected_duration_hours', 'fishing_area_latitude', 'fishing_area_longitude', 'notes', 'departed_at', 'returned_at'];

    protected function casts(): array
    {
        return ['status' => FishingTripStatus::class, 'planned_departure_at' => 'datetime', 'expected_duration_hours' => 'decimal:2', 'fishing_area_latitude' => 'decimal:7', 'fishing_area_longitude' => 'decimal:7', 'departed_at' => 'datetime', 'returned_at' => 'datetime'];
    }

    public function boat(): BelongsTo
    {
        return $this->belongsTo(Boat::class);
    }

    public function fisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fisher_id');
    }

    public function landingSite(): BelongsTo
    {
        return $this->belongsTo(LandingSite::class);
    }

    public function catches(): HasMany
    {
        return $this->hasMany(CatchRecord::class);
    }

    public function crewMembers(): HasMany
    {
        return $this->hasMany(FishingTripCrewMember::class);
    }

    public function hasStatus(FishingTripStatus $status): bool
    {
        return $this->getRawOriginal('status') === $status->value;
    }
}
