<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirebaseSyncFailure extends Model
{
    use HasUuids;

    protected $fillable = ['device_id', 'firebase_uid', 'message_id', 'payload', 'error_code', 'error_message', 'retry_count', 'first_failed_at', 'last_failed_at', 'resolved_at'];

    protected $hidden = ['firebase_uid', 'payload'];

    protected function casts(): array
    {
        return ['payload' => 'array', 'first_failed_at' => 'datetime', 'last_failed_at' => 'datetime', 'resolved_at' => 'datetime'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'device_id');
    }
}
