<?php

namespace App\Services\Transport;

use App\Models\ColdChainAlert;
use App\Models\DeviceAssignment;
use App\Models\IotDevice;
use App\Models\SensorReading;
use App\Models\TransportTrip;
use App\Services\IoT\AlertRuleResolver;

class TransportOperationsSummary
{
    public function __construct(private AlertRuleResolver $rules) {}

    /** @return array<string, mixed> */
    public function dashboard(string $organizationId): array
    {
        $offlineRule = $this->rules->resolve($organizationId, 'DEVICE_OFFLINE');

        return [
            'trip_counts' => TransportTrip::query()->where('organization_id', $organizationId)->selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status'),
            'active_assignments' => DeviceAssignment::query()->where('status', 'ACTIVE')->whereHas('trip', fn ($query) => $query->where('organization_id', $organizationId))->count(),
            'open_alerts' => ColdChainAlert::query()->whereIn('status', ['OPEN', 'ACKNOWLEDGED'])->whereHas('trip', fn ($query) => $query->where('organization_id', $organizationId))->count(),
            'offline_devices' => $offlineRule['is_enabled'] ? IotDevice::query()->where('organization_id', $organizationId)->where(fn ($query) => $query->whereNull('last_seen_at')->orWhere('last_seen_at', '<', now()->subMinutes((int) $offlineRule['duration_minutes'])))->count() : 0,
            'recent_trips' => TransportTrip::query()->where('organization_id', $organizationId)->with(['vehicle:id,name,registration_number', 'activeAssignment.device:id,device_code,display_name'])->latest()->limit(5)->get(),
        ];
    }

    /** @return array<string, mixed> */
    public function sensorSummary(TransportTrip $trip, string $period = 'day'): array
    {
        $periodStart = $period === 'hour' ? now()->subHour() : now()->subDay();
        $query = SensorReading::query()->where('transport_trip_id', $trip->id)->where('recorded_at', '>=', $periodStart);
        $aggregates = (clone $query)->selectRaw('COUNT(*) AS reading_count, AVG(product_temperature) AS average_product_temperature, MIN(product_temperature) AS minimum_product_temperature, MAX(product_temperature) AS maximum_product_temperature, AVG(humidity) AS average_humidity')->first();
        $temperatureRule = $this->rules->resolve($trip->organization_id, 'HIGH_TEMPERATURE');

        return [
            'period' => $period,
            'period_start' => $periodStart->toIso8601String(),
            'reading_count' => (int) ($aggregates?->getAttribute('reading_count') ?? 0),
            'average_product_temperature' => $aggregates?->getAttribute('average_product_temperature'),
            'minimum_product_temperature' => $aggregates?->getAttribute('minimum_product_temperature'),
            'maximum_product_temperature' => $aggregates?->getAttribute('maximum_product_temperature'),
            'average_humidity' => $aggregates?->getAttribute('average_humidity'),
            'critical_readings' => $temperatureRule['is_enabled'] ? (clone $query)->where('product_temperature', '>=', (float) $temperatureRule['critical_threshold'])->count() : 0,
            'latest_reading' => (clone $query)->with('device')->latest('recorded_at')->first(),
        ];
    }
}
