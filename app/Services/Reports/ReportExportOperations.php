<?php

namespace App\Services\Reports;

use App\Enums\ReportExportStatus;
use App\Jobs\GenerateReportExport;
use App\Models\ReportExport;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportExportOperations
{
    public function directory(User $user, int $perPage): LengthAwarePaginator
    {
        return ReportExport::query()
            ->where('organization_id', $user->primaryOrganization()?->id)
            ->latest()
            ->paginate($perPage);
    }

    public function queue(User $user, array $data): ReportExport
    {
        $export = ReportExport::query()->create([
            'organization_id' => $user->primaryOrganization()?->id,
            'requested_by' => $user->id,
            'report_type' => $data['report_type'],
            'filters' => collect($data)->except('report_type')->all(),
            'status' => ReportExportStatus::PENDING,
        ]);
        GenerateReportExport::dispatch($export->id)->afterCommit();

        return $export->fresh() ?? $export;
    }

    public function details(ReportExport $export): ReportExport
    {
        return $export->load('file');
    }
}
