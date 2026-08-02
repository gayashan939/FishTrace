<?php

namespace App\Console\Commands;

use App\Services\Operations\ReleaseReadiness;
use Illuminate\Console\Command;

class ReleaseCheck extends Command
{
    protected $signature = 'fishtrace:release-check
        {--strict : Treat release advisories as failures}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Run non-destructive FishTrace deployment readiness checks';

    public function handle(ReleaseReadiness $readiness): int
    {
        $checks = $readiness->inspect((bool) $this->option('strict'));
        $failed = collect($checks)->where('status', 'fail')->count();
        $warnings = collect($checks)->where('status', 'warn')->count();

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'ready' => $failed === 0,
                'summary' => ['passed' => count($checks) - $failed - $warnings, 'warnings' => $warnings, 'failed' => $failed],
                'checks' => $checks,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->table(['Check', 'Status', 'Result'], array_map(fn (array $check): array => [$check['label'], strtoupper($check['status']), $check['message']], $checks));
            $this->newLine();
            $this->line(sprintf('%d passed, %d warnings, %d failed.', count($checks) - $failed - $warnings, $warnings, $failed));
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
