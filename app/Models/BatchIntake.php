<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BatchIntake extends Model
{
    use HasUuids;

    protected $fillable = ['fish_batch_id', 'processor_organization_id', 'received_by', 'status', 'received_weight_kg', 'rejection_reason', 'notes', 'received_at'];

    protected function casts(): array
    {
        return ['received_weight_kg' => 'decimal:3', 'received_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class, 'fish_batch_id');
    }

    public function processingRecord(): HasOne
    {
        return $this->hasOne(ProcessingRecord::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'processor_organization_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
