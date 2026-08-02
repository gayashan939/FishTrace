<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertRuleConfig extends Model
{
    use HasUuids;

    protected $fillable = ['organization_id', 'scope_key', 'rule_type', 'warning_threshold', 'critical_threshold', 'duration_minutes', 'is_enabled'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    protected function casts(): array
    {
        return ['warning_threshold' => 'decimal:3', 'critical_threshold' => 'decimal:3', 'duration_minutes' => 'integer', 'is_enabled' => 'boolean'];
    }
}
