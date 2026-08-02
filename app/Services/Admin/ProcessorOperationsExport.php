<?php

namespace App\Services\Admin;

use App\Models\FishBatch;
use App\Models\Organization;
use App\Models\ProcessingRecord;
use App\Models\ProcessingType;
use App\Models\QualityGrade;
use App\Models\QualityInspection;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcessorOperationsExport
{
    public function __construct(private ProcessorOperationsQuery $query, private AuditLogger $audit) {}

    public function records(User $actor, array $filters): StreamedResponse
    {
        $rows = $this->query->records($filters)->limit(10000)->get();
        $this->audit->record('PROCESSING_RECORDS_EXPORTED', null, null, ['filters' => $filters, 'row_count' => $rows->count()], $actor);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, ['batch_code', 'organization', 'processing_type', 'status', 'input_weight_kg', 'output_weight_kg', 'waste_weight_kg', 'step_count', 'inspection_count', 'started_at', 'completed_at'], escape: '\\');
            foreach ($rows as $record) {
                if (! $record instanceof ProcessingRecord) {
                    continue;
                }
                $batch = $record->batch;
                $organization = $record->organization;
                $type = $record->processingType;
                fputcsv($out, [$batch instanceof FishBatch ? $batch->batch_code : null, $organization instanceof Organization ? $organization->name : null, $type instanceof ProcessingType ? $type->name : null, $record->getRawOriginal('status'), $record->input_weight_kg, $record->output_weight_kg, $record->waste_weight_kg, $record->steps_count, $record->inspections_count, Carbon::parse($record->started_at)->toIso8601String(), $record->completed_at ? Carbon::parse($record->completed_at)->toIso8601String() : null], escape: '\\');
            }
            fclose($out);
        }, 'fishtrace-processing-records-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function inspections(User $actor, array $filters): StreamedResponse
    {
        $rows = $this->query->inspections($filters)->limit(10000)->get();
        $this->audit->record('QUALITY_INSPECTIONS_EXPORTED', null, null, ['filters' => $filters, 'row_count' => $rows->count()], $actor);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, ['batch_code', 'organization', 'inspector', 'result', 'quality_grade', 'product_temperature', 'ph_level', 'appearance', 'odor', 'inspected_at'], escape: '\\');
            foreach ($rows as $inspection) {
                if (! $inspection instanceof QualityInspection) {
                    continue;
                }
                $batch = $inspection->batch;
                $organization = $inspection->organization;
                $inspector = $inspection->inspector;
                $grade = $inspection->qualityGrade;
                fputcsv($out, [$batch instanceof FishBatch ? $batch->batch_code : null, $organization instanceof Organization ? $organization->name : null, $inspector instanceof User ? $inspector->name : null, $inspection->getRawOriginal('result'), $grade instanceof QualityGrade ? $grade->code : null, $inspection->product_temperature, $inspection->ph_level, $inspection->appearance, $inspection->odor, Carbon::parse($inspection->inspected_at)->toIso8601String()], escape: '\\');
            }
            fclose($out);
        }, 'fishtrace-quality-inspections-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }
}
