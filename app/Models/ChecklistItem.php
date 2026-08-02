<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistItem extends Model
{
    use HasUuids;

    protected $fillable = ['pre_trip_checklist_id', 'item_key', 'label', 'is_mandatory', 'is_completed', 'completed_by', 'completed_at'];

    protected function casts(): array
    {
        return ['is_mandatory' => 'boolean', 'is_completed' => 'boolean', 'completed_at' => 'datetime'];
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(PreTripChecklist::class, 'pre_trip_checklist_id');
    }
}
