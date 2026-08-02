<?php

namespace App\Models;

use App\Enums\InspectionResult;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityInspection extends Model
{
    use HasUuids;

    protected $fillable = ['fish_batch_id', 'processing_record_id', 'organization_id', 'inspector_id', 'quality_grade_id', 'result', 'product_temperature', 'ph_level', 'appearance', 'odor', 'notes', 'inspected_at'];

    protected function casts(): array
    {
        return ['result' => InspectionResult::class, 'product_temperature' => 'decimal:3', 'ph_level' => 'decimal:2', 'inspected_at' => 'datetime'];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class, 'fish_batch_id');
    }

    public function processingRecord(): BelongsTo
    {
        return $this->belongsTo(ProcessingRecord::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function qualityGrade(): BelongsTo
    {
        return $this->belongsTo(QualityGrade::class);
    }
}
