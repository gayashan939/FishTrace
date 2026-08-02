<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrCode extends Model
{
    use HasUuids;

    protected $fillable = ['fish_batch_id', 'public_token', 'revoked_at'];

    protected $hidden = ['public_token'];

    protected function casts(): array
    {
        return ['revoked_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class, 'fish_batch_id');
    }
}
