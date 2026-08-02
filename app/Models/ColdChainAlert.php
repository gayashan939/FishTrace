<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColdChainAlert extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['first_detected_at' => 'datetime', 'last_detected_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(TransportTrip::class, 'transport_trip_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class, 'fish_batch_id');
    }
}
