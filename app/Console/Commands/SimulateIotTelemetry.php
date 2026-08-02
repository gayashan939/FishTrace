<?php

namespace App\Console\Commands;

use App\Contracts\Firebase\FirebaseRealtimeClient;
use App\Models\DeviceAssignment;
use App\Models\IotDevice;
use App\Models\TransportTrip;
use App\Services\IoT\TelemetryImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SimulateIotTelemetry extends Command
{
    protected $signature = 'fishtrace:iot-simulate {--trip=} {--scenario=normal} {--duration=60} {--interval=10} {--firebase} {--mysql}';

    protected $description = 'Generate deterministic ESP32-style telemetry';

    public function handle(FirebaseRealtimeClient $firebase, TelemetryImporter $importer): int
    {
        $trip = TransportTrip::query()->findOrFail($this->option('trip'));
        $assignment = DeviceAssignment::query()->where('transport_trip_id', $trip->id)->where('status', 'ACTIVE')->latest('assigned_at')->first();
        $device = $assignment ? IotDevice::find($assignment->iot_device_id) : null;
        if (! $device?->firebase_uid) {
            $this->error('Trip has no active Firebase-provisioned device.');

            return self::FAILURE;
        }
        mt_srand(42);
        $count = max(1, intdiv((int) $this->option('duration'), max(1, (int) $this->option('interval'))));
        for ($i = 0; $i < $count; $i++) {
            $messageId = (string) Str::uuid();
            $base = match ($this->option('scenario')) {
                'warning' => 5.5, 'critical' => 9.0, default => 3.2
            };
            $recorded = now()->subSeconds(($count - $i - 1) * (int) $this->option('interval'));
            $batchIds = $trip->batches()->pluck('fish_batches.id')->all();
            $payload = ['deviceId' => $device->id, 'tripId' => $trip->id, 'batchIds' => array_fill_keys($batchIds, true), 'productTemperature' => round($base + ($i * .08), 2), 'airTemperature' => round($base + .7, 2), 'humidity' => 82 + ($i % 3), 'latitude' => 6.9271 + ($i * .0001), 'longitude' => 79.8612 + ($i * .0001), 'speedKph' => 38 + $i, 'batteryPercentage' => max(0, 95 - $i), 'signalStrength' => -70 - ($i % 4), 'doorOpen' => $this->option('scenario') === 'critical' && $i % 2 === 0, 'recordedAt' => $recorded->getTimestampMs(), 'schemaVersion' => 1];
            if ($this->option('firebase')) {
                $firebase->set('telemetry/'.$device->firebase_uid.'/'.$messageId, $payload);
                $firebase->set('liveTrips/'.$trip->id, array_merge($payload, ['messageId' => $messageId, 'deviceCode' => $device->device_code, 'primaryBatchId' => $batchIds[0] ?? null]));
            }
            if ($this->option('mysql')) {
                $importer->import($device, $messageId, $payload);
            }
        }
        $this->info("Generated {$count} readings for {$trip->trip_code}.");

        return self::SUCCESS;
    }
}
