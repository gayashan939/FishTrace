<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Boat extends Model
{
    use BelongsToOrganization, HasUuids, SoftDeletes;

    protected $fillable = ['organization_id', 'owner_id', 'registration_number', 'name', 'type', 'capacity_kg', 'length_meters', 'engine_details', 'home_port', 'is_active'];

    protected function casts(): array
    {
        return ['capacity_kg' => 'decimal:3', 'length_meters' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(FishingTrip::class);
    }
}
