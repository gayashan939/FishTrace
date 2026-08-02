<?php

namespace App\Jobs;

use App\Enums\FileCategory;
use App\Enums\NotificationType;
use App\Enums\ReportExportStatus;
use App\Enums\ReportType;
use App\Models\FileAsset;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OperationalNotifier;
use App\Services\Reports\CsvReportRenderer;
use App\Services\Reports\ReportDataService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateReportExport implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [30, 120, 300];

    public int $uniqueFor = 1800;

    public function __construct(public string $reportExportId) {}

    public function uniqueId(): string
    {
        return $this->reportExportId;
    }

    public function handle(ReportDataService $reports, CsvReportRenderer $csv, OperationalNotifier $notifier, AuditLogger $audit): void
    {
        $export = ReportExport::findOrFail($this->reportExportId);
        if ($export->getRawOriginal('status') === ReportExportStatus::COMPLETED->value) {
            return;
        }
        $export->update(['status' => ReportExportStatus::PROCESSING, 'started_at' => now(), 'failure_message' => null]);
        $user = User::findOrFail($export->requested_by);
        $type = ReportType::from($export->getRawOriginal('report_type'));
        $filtersJson = $export->getRawOriginal('filters');
        $filters = is_string($filtersJson) ? json_decode($filtersJson, true, flags: JSON_THROW_ON_ERROR) : [];
        $report = $reports->generate($user, $type, $filters);
        $contents = $csv->render($report);
        $disk = (string) config('filesystems.default', 'local');
        $path = 'organizations/'.$export->organization_id.'/report_exports/'.$export->id.'.csv';
        abort_unless(Storage::disk($disk)->put($path, $contents), 500, 'Unable to store report export.');
        FileAsset::query()->updateOrCreate(
            ['entity_type' => 'report_export', 'entity_id' => $export->id, 'category' => FileCategory::REPORT_EXPORT->value],
            ['organization_id' => $export->organization_id, 'uploaded_by' => $user->id, 'disk' => $disk, 'path' => $path, 'original_name' => $type->value.'-'.$export->id.'.csv', 'mime_type' => 'text/csv', 'extension' => 'csv', 'size_bytes' => strlen($contents), 'sha256' => hash('sha256', $contents)]
        );
        $export->update(['status' => ReportExportStatus::COMPLETED, 'completed_at' => now()]);
        $audit->record('REPORT_EXPORT_COMPLETED', $export, ['status' => ReportExportStatus::PROCESSING->value], ['status' => ReportExportStatus::COMPLETED->value, 'file_asset_id' => FileAsset::query()->where('entity_type', 'report_export')->where('entity_id', $export->id)->value('id')], $user, $export->organization_id);
        $notifier->user($user, NotificationType::REPORT_EXPORT_READY, 'Report export ready', 'Your '.$type->value.' CSV export is ready.', ['report_export_id' => $export->id]);
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
        $export = ReportExport::find($this->reportExportId);
        if ($export === null) {
            return;
        }
        $message = 'Report export failed ('.class_basename($exception).').';
        $export->update(['status' => ReportExportStatus::FAILED, 'failure_message' => $message]);
        $user = User::find($export->requested_by);
        if ($user !== null) {
            $type = ReportType::from($export->getRawOriginal('report_type'));
            app(AuditLogger::class)->record('REPORT_EXPORT_FAILED', $export, null, ['status' => ReportExportStatus::FAILED->value, 'error_message' => $message], $user, $export->organization_id);
            app(OperationalNotifier::class)->user($user, NotificationType::REPORT_EXPORT_FAILED, 'Report export failed', 'Your '.$type->value.' export could not be generated.', ['report_export_id' => $export->id]);
        }
    }
}
