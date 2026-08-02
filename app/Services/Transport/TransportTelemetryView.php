<?php

namespace App\Services\Transport;

use App\Http\Resources\IoT\SensorReadingResource;
use App\Http\Resources\Transport\TransportTripResource;
use App\Models\SensorReading;
use Illuminate\Http\Request;

class TransportTelemetryView
{
    public function dashboard(array $data, Request $request): array
    {
        $data['recent_trips'] = TransportTripResource::collection($data['recent_trips'])->resolve($request);

        return $data;
    }

    public function latest(?SensorReading $reading, int $activeAlertCount, Request $request): ?array
    {
        if ($reading === null) {
            return null;
        }

        return [
            ...(new SensorReadingResource($reading))->resolve($request),
            'active_alert_count' => $activeAlertCount,
        ];
    }

    public function summary(array $data, Request $request): array
    {
        if ($data['latest_reading'] instanceof SensorReading) {
            $data['latest_reading'] = (new SensorReadingResource($data['latest_reading']))->resolve($request);
        }

        return $data;
    }

    public function liveAccess(array $data, Request $request): array
    {
        if ($data['latest_mysql_reading'] instanceof SensorReading) {
            $data['latest_mysql_reading'] = (new SensorReadingResource($data['latest_mysql_reading']))->resolve($request);
        }

        return $data;
    }
}
