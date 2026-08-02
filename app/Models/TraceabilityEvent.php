<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TraceabilityEvent extends Model
{
    use HasUuids;

    protected $fillable = ['fish_batch_id', 'organization_id', 'actor_id', 'event_type', 'title', 'public_data', 'private_data', 'occurred_at'];

    protected $hidden = ['private_data'];

    protected function casts(): array
    {
        return ['public_data' => 'array', 'private_data' => 'array', 'occurred_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class, 'fish_batch_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
