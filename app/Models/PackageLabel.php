<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PackageLabel extends Model
{
    use HasUuids;

    protected $fillable = ['fish_batch_id', 'label_code', 'public_token', 'package_weight_kg', 'package_count', 'printed_at'];

    protected $hidden = ['public_token'];

    protected function casts(): array
    {
        return ['package_weight_kg' => 'decimal:3', 'printed_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class, 'fish_batch_id');
    }

    public function retailReceipt(): HasOne
    {
        return $this->hasOne(RetailReceipt::class);
    }
}
