<?php

namespace App\Services\Audit;

use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogExport
{
    public function __construct(
        private readonly AuditLogQuery $logs,
        private readonly AuditLogger $audit,
    ) {}

    public function download(User $actor, array $filters): StreamedResponse
    {
        $records = $this->logs->exportRecords($actor, $filters);
        $auditedFilters = $filters;
        unset($auditedFilters['per_page']);

        $this->audit->record('AUDIT_EXPORT_DOWNLOADED', null, null, [
            'filters' => $auditedFilters,
            'row_count' => $records->count(),
        ], $actor);

        return response()->streamDownload(function () use ($records): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }

            fputcsv($stream, ['id', 'created_at', 'action', 'entity_type', 'entity_id', 'user_id', 'organization_id', 'request_id', 'ip_address', 'old_values', 'new_values'], escape: '\\');
            foreach ($records as $log) {
                fputcsv($stream, [
                    $log->id,
                    $log->created_at->toIso8601String(),
                    $log->action,
                    $log->auditable_type ? class_basename($log->auditable_type) : null,
                    $log->auditable_id,
                    $log->user_id,
                    $log->organization_id,
                    $log->request_id,
                    $log->ip_address,
                    json_encode($log->old_values, JSON_THROW_ON_ERROR),
                    json_encode($log->new_values, JSON_THROW_ON_ERROR),
                ], escape: '\\');
            }
            fclose($stream);
        }, 'fishtrace-audit-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
