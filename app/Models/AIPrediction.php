<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIPrediction extends Model
{
    use HasUuids;

    protected $table = 'ai_predictions';

    protected $fillable = ['fish_batch_id', 'transport_trip_id', 'requested_by', 'risk_level', 'confidence', 'probabilities', 'recommendation', 'model_version', 'provider', 'predicted_at'];

    protected function casts(): array
    {
        return ['confidence' => 'float', 'probabilities' => 'array', 'predicted_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class, 'fish_batch_id');
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(TransportTrip::class, 'transport_trip_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
