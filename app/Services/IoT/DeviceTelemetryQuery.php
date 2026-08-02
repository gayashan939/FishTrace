<?php

namespace App\Services\IoT;

use App\Http\Resources\IoT\SensorReadingResource;
use App\Models\FirebaseSyncFailure;
use App\Models\IotDevice;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DeviceTelemetryQuery
{
    public function devices(User $user, int $perPage): LengthAwarePaginator
    {
        return IotDevice::query()
            ->when(! $user->hasRole('ADMIN'), fn ($query) => $query->where('organization_id', $user->primaryOrganization()?->id))
            ->latest()
            ->paginate($perPage);
    }

    public function details(IotDevice $device): IotDevice
    {
        return $device->load('assignments.trip');
    }

    public function health(IotDevice $device): array
    {
        $latest = $device->readings()->with('device')->latest('recorded_at')->first();

        return [
            'device' => $device->only(['id', 'device_code', 'display_name', 'status', 'firmware_version', 'battery_percentage', 'signal_strength', 'last_seen_at', 'firebase_auth_enabled', 'credential_version']),
            'latest_reading' => $latest === null ? null : new SensorReadingResource($latest),
            'open_sync_failures' => $device->syncFailures()->whereNull('resolved_at')->count(),
        ];
    }

    public function readings(IotDevice $device, array $filters): LengthAwarePaginator
    {
        $query = $device->readings()->with('device')->latest('recorded_at');
        if (isset($filters['date_from'])) {
            $query->where('recorded_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('recorded_at', '<=', $filters['date_to']);
        }
        $page = $query->paginate((int) ($filters['per_page'] ?? 50));
        $page->setCollection(SensorReadingResource::collection($page->getCollection())->collection);

        return $page;
    }

    public function latestReading(IotDevice $device): ?SensorReadingResource
    {
        $reading = $device->readings()->with('device')->latest('recorded_at')->first();

        return $reading === null ? null : new SensorReadingResource($reading);
    }

    public function syncStatus(IotDevice $device): array
    {
        return [
            'cursor' => $device->syncCursor?->only(['last_recorded_at', 'last_message_id', 'last_successful_sync_at']),
            'unresolved_failures' => FirebaseSyncFailure::query()->where('device_id', $device->id)->whereNull('resolved_at')->latest('last_failed_at')->limit(20)->get(['id', 'message_id', 'error_code', 'retry_count', 'first_failed_at', 'last_failed_at']),
        ];
    }
}
