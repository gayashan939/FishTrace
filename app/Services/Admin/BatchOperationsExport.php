<?php

namespace App\Services\Admin;

use App\Models\FishSpecies;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BatchOperationsExport
{
    public function __construct(
        private readonly BatchOperationsQuery $batches,
        private readonly AuditLogger $audit,
    ) {}

    public function download(User $actor, array $filters): StreamedResponse
    {
        $records = $this->batches->exportRecords($filters);
        $this->audit->record('BATCH_DIRECTORY_EXPORTED', null, null, [
            'filters' => $filters,
            'row_count' => $records->count(),
        ], $actor);

        return response()->streamDownload(function () use ($records): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }

            fputcsv($stream, ['batch_code', 'organization', 'species', 'type', 'status', 'product_type', 'weight_kg', 'recalled', 'public', 'created_at'], escape: '\\');
            foreach ($records as $batch) {
                $organization = $batch->organization;
                $species = $batch->species;
                fputcsv($stream, [
                    $batch->batch_code,
                    $organization instanceof Organization ? $organization->name : null,
                    $species instanceof FishSpecies ? $species->common_name : null,
                    $batch->type,
                    $batch->getRawOriginal('status'),
                    $batch->product_type,
                    $batch->total_weight_kg,
                    $batch->is_recalled ? 'yes' : 'no',
                    $batch->is_public ? 'yes' : 'no',
                    $batch->created_at?->toIso8601String(),
                ], escape: '\\');
            }
            fclose($stream);
        }, 'fishtrace-batches-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
