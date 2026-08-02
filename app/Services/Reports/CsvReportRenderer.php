<?php

namespace App\Services\Reports;

class CsvReportRenderer
{
    public function render(array $report): string
    {
        $stream = fopen('php://temp', 'w+');
        abort_if($stream === false, 500, 'Unable to create report stream.');
        $rows = $report['rows'];
        if ($rows !== []) {
            fputcsv($stream, array_keys($rows[0]), escape: '\\');
            foreach ($rows as $row) {
                fputcsv($stream, array_map(fn ($value) => is_scalar($value) || $value === null ? $value : json_encode($value, JSON_THROW_ON_ERROR), $row), escape: '\\');
            }
        }
        rewind($stream);
        $contents = stream_get_contents($stream);
        fclose($stream);
        abort_if($contents === false, 500, 'Unable to render report.');

        return "\xEF\xBB\xBF".$contents;
    }
}
