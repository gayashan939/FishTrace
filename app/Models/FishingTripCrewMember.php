<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FishingTripCrewMember extends Model
{
    use HasUuids;

    protected $fillable = ['fishing_trip_id', 'name'];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(FishingTrip::class, 'fishing_trip_id');
    }
}
