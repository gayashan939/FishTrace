<?php

namespace App\Console\Commands;

use App\Contracts\Firebase\FirebaseRealtimeClient;
use App\Enums\NotificationType;
use App\Models\IotDevice;
use App\Models\SensorReading;
use App\Services\Audit\AuditLogger;
use App\Services\IoT\TelemetryImporter;
use App\Services\Notifications\OperationalNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SyncFirebaseTelemetry extends Command
{
    protected $signature = 'fishtrace:sync-firebase-telemetry {--limit=} {--device=} {--dry-run} {--retry-failed} {--older-than= : Import messages at least this many minutes old}';

    protected $description = 'Import bounded, unsynchronized Firebase telemetry into MySQL';

    public function handle(FirebaseRealtimeClient $firebase, TelemetryImporter $importer, OperationalNotifier $notifier, AuditLogger $audit): int
    {
        $lock = Cache::lock('fishtrace:firebase-sync', config('fishtrace.firebase.sync_lock_seconds'));
        if (! $lock->get()) {
            $this->warn('A telemetry sync is already running.');

            return self::SUCCESS;
        }
        $imported = $duplicates = $failed = 0;
        try {
            $limit = min(1000, max(1, (int) ($this->option('limit') ?: config('fishtrace.firebase.sync_batch_size'))));
            $olderThan = $this->option('older-than');
            if ($olderThan !== null && (! is_numeric($olderThan) || (int) $olderThan < 0 || (int) $olderThan > 10080)) {
                $this->error('The older-than value must be between 0 and 10080 minutes.');

                return self::INVALID;
            }
            $cutoff = $olderThan === null ? null : now()->subMinutes((int) $olderThan)->getTimestampMs();
            $query = IotDevice::query()->where('status', 'ACTIVE')->where('firebase_auth_enabled', true)->whereNotNull('firebase_uid');
            if ($this->option('device')) {
                $query->where(fn ($q) => $q->where('id', $this->option('device'))->orWhere('device_code', $this->option('device')));
            }
            foreach ($query->limit((int) config('fishtrace.firebase.sync_device_limit'))->get() as $device) {
                $messages = collect($firebase->get('telemetry/'.$device->firebase_uid))->take($limit);
                foreach ($messages as $messageId => $payload) {
                    if (! is_array($payload) || ($payload['syncStatus'] ?? null) === 'SYNCED' || ($this->option('retry-failed') && ($payload['syncStatus'] ?? null) !== 'FAILED') || ($cutoff !== null && (int) ($payload['recordedAt'] ?? PHP_INT_MAX) > $cutoff)) {
                        continue;
                    }
                    if ($this->option('dry-run')) {
                        $this->line("Would import {$device->device_code}/{$messageId}");

                        continue;
                    }
                    try {
                        $before = SensorReading::where('message_id', $messageId)->exists();
                        $importer->import($device, (string) $messageId, $payload);
                        DB::table('firebase_sync_failures')->where('device_id', $device->id)->where('message_id', (string) $messageId)->update(['resolved_at' => now(), 'updated_at' => now()]);
                        $before ? $duplicates++ : $imported++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                        $message = 'Telemetry import failed ('.class_basename($exception).').';
                        $diagnosticPayload = Arr::only($payload, ['tripId', 'productTemperature', 'airTemperature', 'humidity', 'latitude', 'longitude', 'speedKph', 'batteryPercentage', 'signalStrength', 'doorOpen', 'recordedAt', 'schemaVersion']);
                        $existingFailure = DB::table('firebase_sync_failures')->where('device_id', $device->id)->where('message_id', (string) $messageId)->first();
                        $failureId = is_object($existingFailure) ? (string) $existingFailure->id : (string) Str::uuid();
                        DB::table('firebase_sync_failures')->updateOrInsert(
                            ['device_id' => $device->id, 'message_id' => (string) $messageId],
                            ['id' => $failureId, 'firebase_uid' => $device->firebase_uid, 'payload' => json_encode($diagnosticPayload, JSON_THROW_ON_ERROR), 'error_code' => 'IMPORT_FAILED', 'error_message' => $message, 'retry_count' => is_object($existingFailure) ? ((int) $existingFailure->retry_count + 1) : 1, 'first_failed_at' => is_object($existingFailure) ? $existingFailure->first_failed_at : now(), 'last_failed_at' => now(), 'resolved_at' => null, 'created_at' => is_object($existingFailure) ? $existingFailure->created_at : now(), 'updated_at' => now()]
                        );
                        $audit->record('FIREBASE_IMPORT_FAILED', $device, null, ['failure_id' => $failureId, 'message_id' => (string) $messageId, 'error_message' => $message], organizationId: $device->organization_id);
                        $notifier->organizationOnce('firebase-import-failure:'.$failureId, $device->organization_id, NotificationType::FIREBASE_IMPORT_FAILURE, 'Firebase telemetry import failed', 'Telemetry from '.$device->device_code.' could not be imported.', ['device_id' => $device->id, 'failure_id' => $failureId, 'message_id' => (string) $messageId]);
                        $firebase->markSynchronized($device->firebase_uid, (string) $messageId, ['syncStatus' => 'FAILED', 'syncError' => 'IMPORT_FAILED']);
                    }
                }
            }
        } finally {
            $lock->release();
        }
        $this->table(['Imported', 'Duplicates', 'Failed'], [[$imported, $duplicates, $failed]]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
