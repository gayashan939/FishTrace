<?php

namespace App\Models;

use App\Enums\ProcessingStepType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcessingStep extends Model
{
    use HasUuids;

    protected $fillable = ['processing_record_id', 'type', 'sequence', 'status', 'measurements', 'notes', 'performed_by', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return ['type' => ProcessingStepType::class, 'measurements' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function processingRecord(): BelongsTo
    {
        return $this->belongsTo(ProcessingRecord::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
