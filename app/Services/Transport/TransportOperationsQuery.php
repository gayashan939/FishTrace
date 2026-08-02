<?php

namespace App\Services\Transport;

use App\Models\ColdChainAlert;
use App\Models\DeviceAssignment;
use App\Models\IotDevice;
use App\Models\SensorReading;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Pagination\LengthAwarePaginator;

class TransportOperationsQuery
{
    public function vehicles(User $user, int $perPage): LengthAwarePaginator
    {
        return Vehicle::query()
            ->withCount('trips')
            ->when(! $user->hasRole('ADMIN'), fn ($query) => $query->where('organization_id', $user->primaryOrganization()?->id))
            ->latest()
            ->paginate($perPage);
    }

    public function vehicle(Vehicle $vehicle): Vehicle
    {
        return $vehicle->loadCount('trips');
    }

    public function trips(User $user, int $perPage): LengthAwarePaginator
    {
        return TransportTrip::query()
            ->with(['vehicle', 'batches.species', 'activeAssignment.device'])
            ->where('organization_id', $user->primaryOrganization()?->id)
            ->latest()
            ->paginate($perPage);
    }

    public function trip(TransportTrip $trip): TransportTrip
    {
        return $trip->load(['vehicle', 'batches.species', 'activeAssignment.device', 'checklist.items', 'incidents', 'deliveryConfirmation']);
    }

    public function readings(TransportTrip $trip, array $filters): LengthAwarePaginator
    {
        $query = $trip->readings()->with('device')->latest('recorded_at');
        if (isset($filters['date_from'])) {
            $query->where('recorded_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('recorded_at', '<=', $filters['date_to']);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 50));
    }

    public function latestReading(TransportTrip $trip): ?SensorReading
    {
        return SensorReading::query()->with('device')->where('transport_trip_id', $trip->id)->latest('recorded_at')->first();
    }

    public function activeAlertCount(TransportTrip $trip): int
    {
        return $trip->alerts()->whereIn('status', ['OPEN', 'ACKNOWLEDGED'])->count();
    }

    public function liveAccess(TransportTrip $trip): array
    {
        $assignment = DeviceAssignment::query()
            ->with('device')
            ->where('transport_trip_id', $trip->id)
            ->where('status', 'ACTIVE')
            ->latest('assigned_at')
            ->first();
        $device = $assignment?->device;

        return [
            'path' => '/liveTrips/'.$trip->id,
            'trip_id' => $trip->id,
            'assignment_status' => $assignment?->firebase_sync_status,
            'device' => $device instanceof IotDevice ? $device->only(['id', 'device_code', 'display_name', 'status']) : null,
            'latest_mysql_reading' => SensorReading::query()->with('device')->where('transport_trip_id', $trip->id)->latest('recorded_at')->first(),
            'firebase_session_required' => true,
        ];
    }

    public function alerts(TransportTrip $trip, array $filters): LengthAwarePaginator
    {
        return ColdChainAlert::query()
            ->where('transport_trip_id', $trip->id)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['severity'] ?? null, fn ($query, $severity) => $query->where('severity', $severity))
            ->latest('last_detected_at')
            ->paginate((int) ($filters['per_page'] ?? 20));
    }
}
