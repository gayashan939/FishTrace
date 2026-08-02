<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockchainVerification extends Model
{
    use HasUuids;

    protected $fillable = ['blockchain_transaction_id', 'is_valid', 'response', 'verified_at'];

    protected $hidden = ['response'];

    protected function casts(): array
    {
        return ['is_valid' => 'boolean', 'response' => 'array', 'verified_at' => 'datetime'];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(BlockchainTransaction::class, 'blockchain_transaction_id');
    }
}
