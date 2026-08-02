<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneAuditLogs extends Command
{
    protected $signature = 'fishtrace:prune-audit-logs {--days=} {--dry-run}';

    protected $description = 'Delete audit records older than the protected retention period';

    public function handle(): int
    {
        $days = max(365, (int) ($this->option('days') ?: config('fishtrace.audit.retention_days', 2555)));
        $cutoff = now()->subDays($days);
        $query = DB::table('audit_logs')->where('created_at', '<', $cutoff);
        $count = $query->count();
        if ($this->option('dry-run')) {
            $this->info("Would delete {$count} audit records older than {$cutoff->toIso8601String()}.");

            return self::SUCCESS;
        }
        $deleted = 0;
        do {
            $ids = DB::table('audit_logs')->where('created_at', '<', $cutoff)->orderBy('created_at')->limit(1000)->pluck('id');
            $batch = $ids->isEmpty() ? 0 : DB::table('audit_logs')->whereIn('id', $ids)->delete();
            $deleted += $batch;
        } while ($batch === 1000);
        $this->info("Deleted {$deleted} expired audit records.");

        return self::SUCCESS;
    }
}
