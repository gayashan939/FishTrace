<?php

namespace App\Models;

use App\Enums\ReportExportStatus;
use App\Enums\ReportType;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReportExport extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'requested_by', 'report_type', 'filters', 'status', 'failure_message', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return ['report_type' => ReportType::class, 'status' => ReportExportStatus::class, 'filters' => 'array', 'started_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function file(): HasOne
    {
        return $this->hasOne(FileAsset::class, 'entity_id')->where('entity_type', 'report_export');
    }
}
