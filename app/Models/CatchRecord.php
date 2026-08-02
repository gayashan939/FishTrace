<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CatchRecord extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'fishing_trip_id', 'fish_species_id', 'fishing_gear_type_id', 'client_record_id', 'weight_kg', 'quantity', 'allocated_weight_kg', 'caught_at', 'client_created_at'];

    protected function casts(): array
    {
        return ['weight_kg' => 'decimal:3', 'allocated_weight_kg' => 'decimal:3', 'caught_at' => 'datetime', 'client_created_at' => 'datetime'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(FishingTrip::class, 'fishing_trip_id');
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(FishSpecies::class, 'fish_species_id');
    }

    public function gearType(): BelongsTo
    {
        return $this->belongsTo(FishingGearType::class, 'fishing_gear_type_id');
    }

    public function batches(): BelongsToMany
    {
        return $this->belongsToMany(FishBatch::class, 'batch_catches')->withPivot('allocated_weight_kg');
    }
}
