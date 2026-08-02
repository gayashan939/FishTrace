<?php

namespace App\Services\IoT;

use App\Contracts\Firebase\FirebaseRealtimeClient;
use App\Jobs\RequestSpoilagePrediction;
use App\Models\DeviceAssignment;
use App\Models\IotDevice;
use App\Models\SensorReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TelemetryImporter
{
    public function __construct(private FirebaseRealtimeClient $firebase, private ColdChainEvaluator $coldChain) {}

    public function import(IotDevice $device, string $messageId, array $payload): SensorReading
    {
        if ($existing = SensorReading::query()->where('message_id', $messageId)->first()) {
            return $existing;
        }
        validator(array_merge($payload, ['message_id' => $messageId]), ['message_id' => ['required', 'string', 'max:100'], 'tripId' => ['required', 'uuid'], 'productTemperature' => ['nullable', 'numeric', 'between:-20,50'], 'airTemperature' => ['nullable', 'numeric', 'between:-40,80'], 'humidity' => ['nullable', 'numeric', 'between:0,100'], 'latitude' => ['nullable', 'numeric', 'between:-90,90'], 'longitude' => ['nullable', 'numeric', 'between:-180,180'], 'batteryPercentage' => ['nullable', 'numeric', 'between:0,100'], 'recordedAt' => ['required', 'integer'], 'schemaVersion' => ['required', 'integer', 'in:1']])->validate();
        $assignment = DeviceAssignment::query()->where('iot_device_id', $device->id)->where('transport_trip_id', $payload['tripId'])->where('status', 'ACTIVE')->first();
        if (! $assignment) {
            throw ValidationException::withMessages(['tripId' => ['The device is not actively assigned to this trip.']]);
        }

        return DB::transaction(function () use ($device, $assignment, $messageId, $payload): SensorReading {
            $reading = SensorReading::firstOrCreate(['message_id' => $messageId], ['id' => (string) Str::uuid(), 'iot_device_id' => $device->id, 'transport_trip_id' => $assignment->transport_trip_id, 'product_temperature' => $payload['productTemperature'] ?? null, 'air_temperature' => $payload['airTemperature'] ?? null, 'humidity' => $payload['humidity'] ?? null, 'latitude' => $payload['latitude'] ?? null, 'longitude' => $payload['longitude'] ?? null, 'speed_kph' => $payload['speedKph'] ?? null, 'battery_percentage' => $payload['batteryPercentage'] ?? null, 'signal_strength' => $payload['signalStrength'] ?? null, 'door_open' => $payload['doorOpen'] ?? false, 'recorded_at' => Carbon::createFromTimestampMsUTC((int) $payload['recordedAt']), 'imported_at' => now(), 'raw_payload' => $payload]);
            $device->update(['last_seen_at' => now(), 'battery_percentage' => $payload['batteryPercentage'] ?? $device->battery_percentage, 'signal_strength' => $payload['signalStrength'] ?? $device->signal_strength]);
            $this->coldChain->evaluate($reading);
            if (config('fishtrace.ai.auto_predict')) {
                $batchIds = DB::table('transport_batches')->where('transport_trip_id', $assignment->transport_trip_id)->pluck('fish_batch_id');
                foreach ($batchIds as $batchId) {
                    RequestSpoilagePrediction::dispatch((string) $batchId)->afterCommit();
                }
            }
            $this->firebase->markSynchronized((string) $device->firebase_uid, $messageId, ['syncStatus' => 'SYNCED', 'syncedAt' => now()->getTimestampMs()]);

            return $reading;
        });
    }
}
