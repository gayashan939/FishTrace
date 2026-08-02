<?php

namespace App\Services\Admin;

use App\Models\FishBatch;
use App\Models\Organization;
use App\Models\QualityGrade;
use App\Models\QualityInspection;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceOperationsExport
{
    public function __construct(private ComplianceOperationsQuery $query, private AuditLogger $audit) {}

    public function incidents(User $actor, array $filters): StreamedResponse
    {
        $rows = $this->query->incidents($filters)->limit(10000)->get();
        $this->audit->record('COMPLIANCE_INCIDENTS_EXPORTED', null, null, ['filters' => $filters, 'row_count' => $rows->count()], $actor);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            } fputcsv($out, ['batch_code', 'organization', 'result', 'grade', 'temperature', 'ph_level', 'appearance', 'odor', 'inspector', 'inspected_at'], escape: '\\');
            foreach ($rows as $row) {
                if (! $row instanceof QualityInspection) {
                    continue;
                } $batch = $row->batch;
                $organization = $row->organization;
                $grade = $row->qualityGrade;
                $inspector = $row->inspector;
                fputcsv($out, [$batch instanceof FishBatch ? $batch->batch_code : null, $organization instanceof Organization ? $organization->name : null, $row->getRawOriginal('result'), $grade instanceof QualityGrade ? $grade->code : null, $row->product_temperature, $row->ph_level, $row->appearance, $row->odor, $inspector instanceof User ? $inspector->name : null, Carbon::parse($row->inspected_at)->toIso8601String()], escape: '\\');
            } fclose($out);
        }, 'fishtrace-compliance-incidents-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function recalls(User $actor, array $filters): StreamedResponse
    {
        $rows = $this->query->recalls($filters)->limit(10000)->get();
        $this->audit->record('COMPLIANCE_RECALLS_EXPORTED', null, null, ['filters' => $filters, 'row_count' => $rows->count()], $actor);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            } fputcsv($out, ['batch_code', 'source_organization', 'batch_status', 'batch_recalled', 'recalled_inventory_lots', 'trace_events', 'updated_at'], escape: '\\');
            foreach ($rows as $batch) {
                if (! $batch instanceof FishBatch) {
                    continue;
                } $organization = $batch->organization;
                fputcsv($out, [$batch->batch_code, $organization instanceof Organization ? $organization->name : null, $batch->getRawOriginal('status'), $batch->is_recalled ? 'yes' : 'no', $batch->getAttribute('recalled_lots_count'), $batch->getAttribute('events_count'), Carbon::parse($batch->updated_at)->toIso8601String()], escape: '\\');
            } fclose($out);
        }, 'fishtrace-compliance-recalls-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }
}
