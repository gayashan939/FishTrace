<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirebaseSyncCursor extends Model
{
    protected $primaryKey = 'device_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['device_id', 'last_recorded_at', 'last_message_id', 'last_successful_sync_at', 'last_error'];

    protected function casts(): array
    {
        return ['last_recorded_at' => 'datetime', 'last_successful_sync_at' => 'datetime'];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(IotDevice::class, 'device_id');
    }
}
