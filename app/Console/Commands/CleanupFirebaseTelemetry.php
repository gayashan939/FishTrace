<?php

namespace App\Console\Commands;

use App\Contracts\Firebase\FirebaseRealtimeClient;
use App\Models\IotDevice;
use App\Services\Settings\SystemSettings;
use Illuminate\Console\Command;

class CleanupFirebaseTelemetry extends Command
{
    protected $signature = 'fishtrace:cleanup-firebase-telemetry {--dry-run} {--limit=250}';

    protected $description = 'Delete only synchronized Firebase telemetry older than retention';

    public function handle(FirebaseRealtimeClient $firebase, SystemSettings $settings): int
    {
        $cutoff = now()->subHours($settings->integer('telemetry_retention_hours'))->getTimestampMs();
        $deleted = 0;
        foreach (IotDevice::whereNotNull('firebase_uid')->limit((int) config('fishtrace.firebase.sync_device_limit'))->get() as $device) {
            foreach (collect($firebase->get('telemetry/'.$device->firebase_uid))->take((int) $this->option('limit')) as $messageId => $payload) {
                if (is_array($payload) && ($payload['syncStatus'] ?? null) === 'SYNCED' && (int) ($payload['recordedAt'] ?? PHP_INT_MAX) < $cutoff) {
                    if (! $this->option('dry-run')) {
                        $firebase->remove('telemetry/'.$device->firebase_uid.'/'.$messageId);
                    }
                    $deleted++;
                }
            }
        }
        $this->info(($this->option('dry-run') ? 'Would delete ' : 'Deleted ').$deleted.' synchronized records.');

        return self::SUCCESS;
    }
}
