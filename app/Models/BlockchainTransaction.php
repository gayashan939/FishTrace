<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlockchainTransaction extends Model
{
    use HasUuids;

    protected $fillable = ['event_hash', 'transaction_reference', 'network', 'contract_address', 'status', 'attempts', 'error_message', 'submitted_at', 'confirmed_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime', 'confirmed_at' => 'datetime'];
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(BlockchainVerification::class);
    }
}
