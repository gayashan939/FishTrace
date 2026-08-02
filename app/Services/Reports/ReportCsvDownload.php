<?php

namespace App\Services\Reports;

use App\Enums\ReportType;
use App\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCsvDownload
{
    public function __construct(
        private readonly ReportDataService $reports,
        private readonly CsvReportRenderer $renderer,
    ) {}

    public function download(User $user, ReportType $type, array $filters): StreamedResponse
    {
        $contents = $this->renderer->render($this->reports->generate($user, $type, $filters));

        return response()->streamDownload(
            static fn () => print $contents,
            $type->value.'-'.now()->format('Ymd-His').'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff'],
        );
    }
}
