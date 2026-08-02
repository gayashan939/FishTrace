<?php

namespace App\Http\Resources\Reports;

use App\Models\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class ReportExportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $export = $this->model();

        return [
            'id' => $export->id,
            'organization_id' => $export->organization_id,
            'requested_by' => $export->requested_by,
            'report_type' => $export->report_type,
            'filters' => $export->filters,
            'status' => $export->status,
            'started_at' => $export->started_at,
            'completed_at' => $export->completed_at,
            'created_at' => $export->created_at,
            'updated_at' => $export->updated_at,
        ];
    }

    private function model(): ReportExport
    {
        if (! $this->resource instanceof ReportExport) {
            throw new LogicException('ReportExportResource requires a ReportExport model.');
        }

        return $this->resource;
    }
}
