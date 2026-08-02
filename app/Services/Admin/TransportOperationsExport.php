<?php

namespace App\Services\Admin;

use App\Models\ColdChainAlert;
use App\Models\IotDevice;
use App\Models\SensorReading;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransportOperationsExport
{
    public function __construct(private TransportOperationsQuery $query, private AuditLogger $audit) {}

    public function telemetry(User $actor, array $filters): StreamedResponse
    {
        $records = $this->query->readings($filters)->limit(10000)->get();
        $this->audit->record('TELEMETRY_DIRECTORY_EXPORTED', null, null, ['filters' => $filters, 'row_count' => $records->count()], $actor);

        return response()->streamDownload(function () use ($records): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }
            fputcsv($stream, ['recorded_at', 'trip_code', 'device_code', 'product_temperature', 'air_temperature', 'humidity', 'speed_kph', 'battery_percentage', 'signal_strength', 'door_open', 'imported_at'], escape: '\\');
            foreach ($records as $reading) {
                if (! $reading instanceof SensorReading) {
                    continue;
                }
                $trip = $reading->trip;
                $device = $reading->device;
                fputcsv($stream, [Carbon::parse($reading->recorded_at)->toIso8601String(), $trip instanceof TransportTrip ? $trip->trip_code : null, $device instanceof IotDevice ? $device->device_code : null, $reading->product_temperature, $reading->air_temperature, $reading->humidity, $reading->speed_kph, $reading->battery_percentage, $reading->signal_strength, $reading->door_open ? 'yes' : 'no', Carbon::parse($reading->imported_at)->toIso8601String()], escape: '\\');
            }
            fclose($stream);
        }, 'fishtrace-telemetry-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function alerts(User $actor, array $filters): StreamedResponse
    {
        $records = $this->query->alerts($filters)->limit(10000)->get();
        $this->audit->record('COLD_CHAIN_ALERTS_EXPORTED', null, null, ['filters' => $filters, 'row_count' => $records->count()], $actor);

        return response()->streamDownload(function () use ($records): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                return;
            }
            fputcsv($stream, ['trip_code', 'type', 'severity', 'status', 'measured_value', 'threshold_value', 'first_detected_at', 'last_detected_at', 'resolved_at'], escape: '\\');
            foreach ($records as $alert) {
                if (! $alert instanceof ColdChainAlert) {
                    continue;
                }
                $trip = $alert->trip;
                fputcsv($stream, [$trip instanceof TransportTrip ? $trip->trip_code : null, $alert->type, $alert->severity, $alert->status, $alert->measured_value, $alert->threshold_value, Carbon::parse($alert->first_detected_at)->toIso8601String(), Carbon::parse($alert->last_detected_at)->toIso8601String(), $alert->resolved_at ? Carbon::parse($alert->resolved_at)->toIso8601String() : null], escape: '\\');
            }
            fclose($stream);
        }, 'fishtrace-cold-chain-alerts-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }
}
