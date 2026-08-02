<?php

namespace App\Services\Admin;

use App\Models\Boat;
use App\Models\ColdChainAlert;
use App\Models\FishBatch;
use App\Models\FishSpecies;
use App\Models\IotDevice;
use App\Models\Organization;
use App\Models\ProcessingType;
use App\Models\QualityGrade;
use App\Models\RetailLocation;
use App\Models\Role;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class AdminFilterOptions
{
    public function organizations(?string $type = null, bool $activeOnly = false): Collection
    {
        return Organization::query()
            ->when($type, fn ($query, string $value) => $query->where('type', $value))
            ->when($activeOnly, fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get($activeOnly ? ['*'] : ['id', 'name']);
    }

    public function usersByRole(string $role): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', $role))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function boats(): Collection
    {
        return Boat::query()->orderBy('registration_number')->get(['id', 'name', 'registration_number']);
    }

    public function species(): Collection
    {
        return FishSpecies::query()->orderBy('common_name')->get(['id', 'common_name']);
    }

    public function batchTypes(): SupportCollection
    {
        return FishBatch::query()->distinct()->orderBy('type')->pluck('type');
    }

    public function roles(): Collection
    {
        return Role::query()->orderBy('name')->get();
    }

    public function retailLocations(): Collection
    {
        return RetailLocation::query()->orderBy('name')->get(['id', 'organization_id', 'name']);
    }

    public function processingTypes(): Collection
    {
        return ProcessingType::query()->orderBy('name')->get();
    }

    public function qualityGrades(): Collection
    {
        return QualityGrade::query()->orderBy('rank')->get();
    }

    public function vehicles(): Collection
    {
        return Vehicle::query()->orderBy('registration_number')->get(['id', 'name', 'registration_number']);
    }

    public function devices(): Collection
    {
        return IotDevice::query()->orderBy('device_code')->get(['id', 'device_code']);
    }

    public function transportTrips(int $limit = 500): Collection
    {
        return TransportTrip::query()->latest()->limit($limit)->get(['id', 'trip_code']);
    }

    public function alertTypes(): SupportCollection
    {
        return ColdChainAlert::query()->distinct()->orderBy('type')->pluck('type');
    }
}
