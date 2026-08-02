<?php

namespace App\Services\Admin;

use App\Models\ColdChainAlert;
use App\Models\FirebaseSyncFailure;
use App\Models\IotDevice;
use App\Models\SensorReading;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;

class TransportOperationsQuery
{
    public function transporters(array $filters): Builder
    {
        return User::query()->whereHas('roles', fn (Builder $q): Builder => $q->where('name', 'TRANSPORTER'))->with('organizations:id,name,code,type')->withCount('transportTrips')
            ->when($filters['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->when($filters['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->whereHas('organizations', fn (Builder $o): Builder => $o->where('organizations.id', $id)))
            ->when($filters['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))->orderBy('name');
    }

    public function vehicles(array $filters): Builder
    {
        return Vehicle::query()->with('organization:id,name,code')->withCount('trips')
            ->when($filters['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->where('name', 'like', "%{$term}%")->orWhere('registration_number', 'like', "%{$term}%")))
            ->when($filters['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organization_id', $id))->when(isset($filters['is_active']), fn (Builder $q): Builder => $q->where('is_active', $filters['is_active']))->orderBy('registration_number');
    }

    public function trips(array $filters): Builder
    {
        return TransportTrip::query()->with(['organization:id,name,code', 'vehicle:id,name,registration_number', 'creator:id,name', 'activeAssignment.device:id,device_code,display_name,status'])->withCount(['batches', 'readings', 'alerts'])->withMin('readings', 'product_temperature')->withMax('readings', 'product_temperature')
            ->when($filters['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->where('trip_code', 'like', "%{$term}%")->orWhere('driver_name', 'like', "%{$term}%")->orWhere('origin', 'like', "%{$term}%")->orWhere('destination', 'like', "%{$term}%")))
            ->when($filters['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organization_id', $id))->when($filters['vehicle_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('vehicle_id', $id))->when($filters['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $date): Builder => $q->where('created_at', '>=', $date.' 00:00:00'))->when($filters['date_to'] ?? null, fn (Builder $q, string $date): Builder => $q->where('created_at', '<=', $date.' 23:59:59'))->latest();
    }

    public function devices(array $filters): Builder
    {
        return IotDevice::query()->with(['organization:id,name,code', 'syncCursor', 'assignments' => fn ($q) => $q->latest()->limit(1), 'assignments.trip:id,trip_code,status'])->withCount(['readings', 'syncFailures as unresolved_failures_count' => fn ($q) => $q->whereNull('resolved_at')])
            ->when($filters['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->where('device_code', 'like', "%{$term}%")->orWhere('display_name', 'like', "%{$term}%")->orWhere('serial_number', 'like', "%{$term}%")))
            ->when($filters['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organization_id', $id))->when($filters['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))->orderBy('device_code');
    }

    public function readings(array $filters): Builder
    {
        return SensorReading::query()->with(['device:id,device_code,display_name,organization_id', 'trip:id,trip_code,organization_id,origin,destination'])
            ->when($filters['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->whereHas('device', fn (Builder $d): Builder => $d->where('device_code', 'like', "%{$term}%"))->orWhereHas('trip', fn (Builder $t): Builder => $t->where('trip_code', 'like', "%{$term}%"))))
            ->when($filters['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->whereHas('trip', fn (Builder $t): Builder => $t->where('organization_id', $id)))->when($filters['device_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('iot_device_id', $id))->when($filters['trip_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('transport_trip_id', $id))
            ->when($filters['date_from'] ?? null, fn (Builder $q, string $date): Builder => $q->where('recorded_at', '>=', $date.' 00:00:00'))->when($filters['date_to'] ?? null, fn (Builder $q, string $date): Builder => $q->where('recorded_at', '<=', $date.' 23:59:59'))->latest('recorded_at');
    }

    public function alerts(array $filters): Builder
    {
        return ColdChainAlert::query()->with(['trip:id,trip_code,organization_id,origin,destination', 'trip.organization:id,name,code', 'batch:id,batch_code'])
            ->when($filters['q'] ?? null, fn (Builder $q, string $term): Builder => $q->whereHas('trip', fn (Builder $t): Builder => $t->where('trip_code', 'like', "%{$term}%")))
            ->when($filters['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->whereHas('trip', fn (Builder $t): Builder => $t->where('organization_id', $id)))->when($filters['trip_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('transport_trip_id', $id))->when($filters['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))->when($filters['severity'] ?? null, fn (Builder $q, string $severity): Builder => $q->where('severity', $severity))->when($filters['type'] ?? null, fn (Builder $q, string $type): Builder => $q->where('type', $type))->latest('last_detected_at');
    }

    public function syncFailures(array $filters): Builder
    {
        return FirebaseSyncFailure::query()->with('device:id,device_code,display_name,organization_id')->when($filters['device_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('device_id', $id))->when($filters['status'] ?? null, fn (Builder $q, string $status): Builder => $status === 'RESOLVED' ? $q->whereNotNull('resolved_at') : $q->whereNull('resolved_at'))->latest('last_failed_at');
    }
}
