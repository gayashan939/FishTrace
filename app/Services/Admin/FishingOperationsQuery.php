<?php

namespace App\Services\Admin;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\FishingTrip;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FishingOperationsQuery
{
    public function fishers(array $filters): Builder
    {
        [$sort, $direction] = $this->sorting($filters, ['name', 'email', 'created_at'], 'name');

        return User::query()->whereHas('roles', fn (Builder $query): Builder => $query->where('name', 'FISHER'))
            ->with(['organizations:id,name,code,type,is_active'])
            ->withCount(['ownedBoats', 'fishingTrips'])
            ->when($filters['q'] ?? null, fn (Builder $query, string $term): Builder => $query->where(fn (Builder $query): Builder => $query->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->when($filters['organization_id'] ?? null, fn (Builder $query, string $id): Builder => $query->whereHas('organizations', fn (Builder $organizations): Builder => $organizations->where('organizations.id', $id)))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->orderBy($sort, $direction)->orderBy('id');
    }

    public function boats(array $filters): Builder
    {
        [$sort, $direction] = $this->sorting($filters, ['name', 'registration_number', 'capacity_kg', 'created_at'], 'registration_number');

        return Boat::query()->with(['organization:id,name,code', 'owner:id,name,email'])->withCount('trips')
            ->when($filters['q'] ?? null, fn (Builder $query, string $term): Builder => $query->where(fn (Builder $query): Builder => $query->where('name', 'like', "%{$term}%")->orWhere('registration_number', 'like', "%{$term}%")))
            ->when($filters['organization_id'] ?? null, fn (Builder $query, string $id): Builder => $query->where('organization_id', $id))
            ->when($filters['fisher_id'] ?? null, fn (Builder $query, string $id): Builder => $query->where('owner_id', $id))
            ->when(isset($filters['is_active']), fn (Builder $query): Builder => $query->where('is_active', $filters['is_active']))
            ->orderBy($sort, $direction)->orderBy('id');
    }

    public function trips(array $filters): Builder
    {
        [$sort, $direction] = $this->sorting($filters, ['trip_code', 'status', 'departed_at', 'created_at'], 'created_at');

        return FishingTrip::query()->with(['organization:id,name,code', 'fisher:id,name,email', 'boat:id,name,registration_number,capacity_kg', 'landingSite:id,name,district'])->withCount('catches')->withSum('catches', 'weight_kg')->withSum('catches', 'allocated_weight_kg')
            ->when($filters['q'] ?? null, fn (Builder $query, string $term): Builder => $query->where(fn (Builder $query): Builder => $query->where('trip_code', 'like', "%{$term}%")->orWhere('general_catch_area', 'like', "%{$term}%")))
            ->when($filters['organization_id'] ?? null, fn (Builder $query, string $id): Builder => $query->where('organization_id', $id))
            ->when($filters['fisher_id'] ?? null, fn (Builder $query, string $id): Builder => $query->where('fisher_id', $id))
            ->when($filters['boat_id'] ?? null, fn (Builder $query, string $id): Builder => $query->where('boat_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date): Builder => $query->where('departed_at', '>=', $date.' 00:00:00'))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date): Builder => $query->where('departed_at', '<=', $date.' 23:59:59'))
            ->orderBy($sort, $direction)->orderBy('id');
    }

    public function catches(array $filters): Builder
    {
        [$sort, $direction] = $this->sorting($filters, ['caught_at', 'weight_kg', 'created_at'], 'caught_at');
        $ledgerSql = '(SELECT COALESCE(SUM(batch_catches.allocated_weight_kg), 0) FROM batch_catches WHERE batch_catches.catch_record_id = catch_records.id)';

        return CatchRecord::query()->select('catch_records.*')->selectSub(DB::table('batch_catches')->selectRaw('COALESCE(SUM(allocated_weight_kg), 0)')->whereColumn('catch_record_id', 'catch_records.id'), 'ledger_allocated_weight_kg')
            ->with(['organization:id,name,code', 'trip:id,trip_code,fisher_id,boat_id,status', 'trip.fisher:id,name', 'trip.boat:id,name,registration_number', 'species:id,common_name,scientific_name', 'gearType:id,name'])->withCount('batches')
            ->when($filters['q'] ?? null, fn (Builder $query, string $term): Builder => $query->whereHas('trip', fn (Builder $trips): Builder => $trips->where('trip_code', 'like', "%{$term}%")))
            ->when($filters['organization_id'] ?? null, fn (Builder $query, string $id): Builder => $query->where('organization_id', $id))
            ->when($filters['fisher_id'] ?? null, fn (Builder $query, string $id): Builder => $query->whereHas('trip', fn (Builder $trips): Builder => $trips->where('fisher_id', $id)))
            ->when($filters['boat_id'] ?? null, fn (Builder $query, string $id): Builder => $query->whereHas('trip', fn (Builder $trips): Builder => $trips->where('boat_id', $id)))
            ->when($filters['species_id'] ?? null, fn (Builder $query, string $id): Builder => $query->where('fish_species_id', $id))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date): Builder => $query->where('caught_at', '>=', $date.' 00:00:00'))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date): Builder => $query->where('caught_at', '<=', $date.' 23:59:59'))
            ->when($filters['allocation'] ?? null, function (Builder $query, string $allocation) use ($ledgerSql): void {
                if ($allocation === 'UNALLOCATED') {
                    $query->whereRaw($ledgerSql.' = 0');
                } elseif ($allocation === 'PARTIAL') {
                    $query->whereRaw($ledgerSql.' > 0 AND '.$ledgerSql.' < catch_records.weight_kg');
                } elseif ($allocation === 'FULL') {
                    $query->whereRaw($ledgerSql.' = catch_records.weight_kg');
                } elseif ($allocation === 'OVERALLOCATED') {
                    $query->whereRaw($ledgerSql.' > catch_records.weight_kg');
                } elseif ($allocation === 'MISMATCH') {
                    $query->whereRaw('catch_records.allocated_weight_kg <> '.$ledgerSql);
                }
            })
            ->orderBy($sort, $direction)->orderBy('id');
    }

    private function sorting(array $filters, array $allowed, string $default): array
    {
        return [in_array($filters['sort'] ?? null, $allowed, true) ? $filters['sort'] : $default, ($filters['direction'] ?? null) === 'asc' ? 'asc' : 'desc'];
    }
}
