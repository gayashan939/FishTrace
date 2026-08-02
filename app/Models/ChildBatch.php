<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildBatch extends Model
{
    use HasUuids;

    protected $fillable = ['parent_batch_id', 'child_batch_id', 'allocated_weight_kg', 'created_by'];

    protected function casts(): array
    {
        return ['allocated_weight_kg' => 'decimal:3'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class, 'parent_batch_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class, 'child_batch_id');
    }
}
