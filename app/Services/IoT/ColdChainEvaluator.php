<?php

namespace App\Services\IoT;

use App\Enums\NotificationType;
use App\Models\ColdChainAlert;
use App\Models\SensorReading;
use App\Models\TraceabilityEvent;
use App\Models\TransportTrip;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OperationalNotifier;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ColdChainEvaluator
{
    public function __construct(private OperationalNotifier $notifier, private AuditLogger $audit, private AlertRuleResolver $rules) {}

    public function evaluate(SensorReading $reading): void
    {
        $trip = TransportTrip::query()->findOrFail($reading->transport_trip_id);
        $temperature = $reading->product_temperature === null ? null : (float) $reading->product_temperature;
        $high = $this->rules->resolve($trip->organization_id, 'HIGH_TEMPERATURE');
        $warningTemperature = (float) $high['warning_threshold'];
        $criticalTemperature = (float) $high['critical_threshold'];
        if (! $high['is_enabled'] || $temperature === null || $temperature <= $warningTemperature) {
            $this->resolve($reading, 'HIGH_TEMPERATURE');
            $this->resolve($reading, 'CRITICAL_TEMPERATURE');
        } elseif ($temperature >= $criticalTemperature) {
            $this->openOrRefresh($reading, 'CRITICAL_TEMPERATURE', 'CRITICAL', $temperature, $criticalTemperature);
            $this->resolve($reading, 'HIGH_TEMPERATURE');
        } elseif ($this->persisted($reading, (int) $high['duration_minutes'], fn (SensorReading $candidate): bool => $candidate->product_temperature !== null && (float) $candidate->product_temperature > $warningTemperature)) {
            $this->openOrRefresh($reading, 'HIGH_TEMPERATURE', 'WARNING', $temperature, $warningTemperature);
            $this->resolve($reading, 'CRITICAL_TEMPERATURE');
        }

        $low = $this->rules->resolve($trip->organization_id, 'LOW_TEMPERATURE');
        $lowThreshold = (float) $low['warning_threshold'];
        if ($low['is_enabled'] && $temperature !== null && $temperature < $lowThreshold) {
            if ($this->persisted($reading, (int) $low['duration_minutes'], fn (SensorReading $candidate): bool => $candidate->product_temperature !== null && (float) $candidate->product_temperature < $lowThreshold)) {
                $this->openOrRefresh($reading, 'LOW_TEMPERATURE', 'WARNING', $temperature, $lowThreshold);
            }
        } else {
            $this->resolve($reading, 'LOW_TEMPERATURE');
        }

        $battery = $this->rules->resolve($trip->organization_id, 'LOW_BATTERY');
        $batteryLevel = $reading->battery_percentage === null ? null : (float) $reading->battery_percentage;
        $batteryWarning = (float) $battery['warning_threshold'];
        $batteryCritical = (float) $battery['critical_threshold'];
        if ($battery['is_enabled'] && $batteryLevel !== null && $batteryLevel < $batteryWarning) {
            if ($this->persisted($reading, (int) $battery['duration_minutes'], fn (SensorReading $candidate): bool => $candidate->battery_percentage !== null && (float) $candidate->battery_percentage < $batteryWarning)) {
                $critical = $batteryLevel < $batteryCritical;
                $this->openOrRefresh($reading, 'LOW_BATTERY', $critical ? 'CRITICAL' : 'WARNING', $batteryLevel, $critical ? $batteryCritical : $batteryWarning);
            }
        } else {
            $this->resolve($reading, 'LOW_BATTERY');
        }

        $gps = $this->rules->resolve($trip->organization_id, 'GPS_UNAVAILABLE');
        $gpsMissing = $reading->latitude === null || $reading->longitude === null;
        $this->evaluateBooleanRule($reading, 'GPS_UNAVAILABLE', $gps, $gpsMissing, fn (SensorReading $candidate): bool => $candidate->latitude === null || $candidate->longitude === null);

        $door = $this->rules->resolve($trip->organization_id, 'DOOR_OPENED');
        $this->evaluateBooleanRule($reading, 'DOOR_OPENED', $door, $reading->door_open, fn (SensorReading $candidate): bool => $candidate->door_open);
    }

    private function evaluateBooleanRule(SensorReading $reading, string $type, array $rule, bool $violated, Closure $condition): void
    {
        if (! $rule['is_enabled'] || ! $violated) {
            $this->resolve($reading, $type);

            return;
        }
        if ($this->persisted($reading, (int) $rule['duration_minutes'], $condition)) {
            $this->openOrRefresh($reading, $type, 'WARNING', 1, 0);
        }
    }

    private function persisted(SensorReading $reading, int $durationMinutes, Closure $condition): bool
    {
        if ($durationMinutes === 0) {
            return $condition($reading);
        }
        $recordedAt = Carbon::parse($reading->recorded_at);
        $cutoff = $recordedAt->copy()->subMinutes($durationMinutes);
        $candidates = SensorReading::query()->where('transport_trip_id', $reading->transport_trip_id)->whereBetween('recorded_at', [$cutoff->copy()->subMinutes(5), $recordedAt])->oldest('recorded_at')->get();
        $anchor = $candidates->filter(fn (SensorReading $candidate): bool => Carbon::parse($candidate->recorded_at)->lessThanOrEqualTo($cutoff))->last();
        if (! $anchor instanceof SensorReading || ! $condition($anchor)) {
            return false;
        }

        $anchorTime = Carbon::parse($anchor->recorded_at);

        return $candidates->filter(fn (SensorReading $candidate): bool => Carbon::parse($candidate->recorded_at)->greaterThanOrEqualTo($anchorTime))->every($condition);
    }

    private function openOrRefresh(SensorReading $reading, string $type, string $severity, float $measured, float $threshold): void
    {
        $alert = ColdChainAlert::query()->where('transport_trip_id', $reading->transport_trip_id)->where('type', $type)->whereIn('status', ['OPEN', 'ACKNOWLEDGED'])->first();
        if ($alert) {
            $alert->update(['severity' => $severity, 'measured_value' => $measured, 'threshold_value' => $threshold, 'last_detected_at' => $reading->recorded_at]);

            return;
        }
        $alert = ColdChainAlert::create(['transport_trip_id' => $reading->transport_trip_id, 'type' => $type, 'severity' => $severity, 'status' => 'OPEN', 'measured_value' => $measured, 'threshold_value' => $threshold, 'first_detected_at' => $reading->recorded_at, 'last_detected_at' => $reading->recorded_at]);
        $trip = TransportTrip::findOrFail($reading->transport_trip_id);
        $this->audit->record('COLD_CHAIN_ALERT_CREATED', $alert, null, ['type' => $type, 'severity' => $severity, 'measured_value' => $measured, 'threshold_value' => $threshold], organizationId: $trip->organization_id);
        $notificationType = match ($type) {
            'CRITICAL_TEMPERATURE' => NotificationType::CRITICAL_TEMPERATURE,
            'HIGH_TEMPERATURE' => NotificationType::HIGH_TEMPERATURE,
            'LOW_BATTERY' => NotificationType::LOW_BATTERY,
            default => null,
        };
        if ($notificationType !== null) {
            $this->notifier->organization($trip->organization_id, $notificationType, str_replace('_', ' ', ucfirst(mb_strtolower($type))), 'A new '.$severity.' '.$type.' alert was detected.', ['alert_id' => $alert->id, 'transport_trip_id' => $trip->id, 'measured_value' => $measured, 'threshold_value' => $threshold]);
        }
        if ($severity === 'CRITICAL') {
            $batchIds = DB::table('transport_batches')->where('transport_trip_id', $reading->transport_trip_id)->pluck('fish_batch_id');
            foreach ($batchIds as $batchId) {
                TraceabilityEvent::create(['id' => (string) Str::uuid(), 'fish_batch_id' => $batchId, 'event_type' => 'COLD_CHAIN_VIOLATION', 'title' => 'Critical cold-chain condition detected', 'public_data' => ['severity' => $severity], 'occurred_at' => $reading->recorded_at]);
            }
        }
    }

    private function resolve(SensorReading $reading, string $type): void
    {
        ColdChainAlert::query()->where('transport_trip_id', $reading->transport_trip_id)->where('type', $type)->whereIn('status', ['OPEN', 'ACKNOWLEDGED'])->update(['status' => 'RESOLVED', 'resolved_at' => $reading->recorded_at, 'last_detected_at' => $reading->recorded_at]);
    }
}
