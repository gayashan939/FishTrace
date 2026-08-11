<?php

namespace App\Services\Transport;

use App\Enums\BatchStatus;
use App\Models\ColdChainAlert;
use App\Models\DeviceAssignment;
use App\Models\IotDevice;
use App\Models\SensorReading;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class TransportOperationsQuery
{
    public function availableBatches(int $perPage): LengthAwarePaginator
    {
        return $this->availableBatchQuery()->latest()->paginate($perPage);
    }

    public function resolveAvailableBatch(string $scannedValue): ?\App\Models\FishBatch
    {
        $value = trim($scannedValue);
        $path = parse_url($value, PHP_URL_PATH);
        $token = is_string($path) ? basename(trim($path, '/')) : $value;

        return $this->availableBatchQuery()
            ->where(function ($query) use ($value, $token): void {
                if (Str::isUuid($value)) {
                    $query->orWhereKey($value);
                }
                if (Str::isUuid($token)) {
                    $query->orWhereKey($token);
                }
                $query->orWhere('batch_code', $value)
                    ->orWhere('batch_code', $token)
                    ->orWhereHas('qrCode', fn ($qr) => $qr->where('public_token', $token)->whereNull('revoked_at'));
            })
            ->first();
    }

    private function availableBatchQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \App\Models\FishBatch::query()
            ->with(['species', 'qrCode'])
            ->whereIn('status', [BatchStatus::PROCESSED, BatchStatus::READY_FOR_TRANSPORT])
            ->where('is_recalled', false)
            ->whereDoesntHave('transportTrips', fn ($query) => $query->whereIn('transport_trips.status', ['DRAFT', 'READY', 'ACTIVE']));
    }

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
            ->with(['vehicle', 'batches.species', 'batches.qrCode', 'activeAssignment.device', 'latestReading', 'checklist.items', 'deliveryConfirmation'])
            ->where('organization_id', $user->primaryOrganization()?->id)
            ->latest()
            ->paginate($perPage);
    }

    public function trip(TransportTrip $trip): TransportTrip
    {
        return $trip->load(['vehicle', 'batches.species', 'activeAssignment.device', 'latestReading', 'checklist.items', 'incidents', 'deliveryConfirmation']);
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
