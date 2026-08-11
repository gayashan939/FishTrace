<?php

namespace App\Models;

use App\Enums\ProcessingStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProcessingRecord extends Model
{
    use HasUuids;

    protected $fillable = ['fish_batch_id', 'batch_intake_id', 'organization_id', 'created_by', 'processing_type_id', 'operator_name', 'processing_area', 'status', 'input_weight_kg', 'output_weight_kg', 'waste_weight_kg', 'notes', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return ['status' => ProcessingStatus::class, 'input_weight_kg' => 'decimal:3', 'output_weight_kg' => 'decimal:3', 'waste_weight_kg' => 'decimal:3', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class, 'fish_batch_id');
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(BatchIntake::class, 'batch_intake_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ProcessingStep::class)->orderBy('sequence');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(QualityInspection::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function processingType(): BelongsTo
    {
        return $this->belongsTo(ProcessingType::class);
    }

    public function hasStatus(ProcessingStatus $status): bool
    {
        return $this->getRawOriginal('status') === $status->value;
    }
}
