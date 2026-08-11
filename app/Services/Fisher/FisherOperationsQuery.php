<?php

namespace App\Services\Fisher;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\FishBatch;
use App\Models\FishingTrip;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FisherOperationsQuery
{
    public function boats(User $user, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = Boat::query()->where('organization_id', $user->primaryOrganization()?->id);
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$search}%")->orWhere('registration_number', 'like', "%{$search}%"));
        }
        if (isset($filters['status'])) {
            $query->where('is_active', strtoupper($filters['status']) === 'ACTIVE');
        }

        return $this->sort($query, $filters, ['name', 'registration_number', 'created_at', 'updated_at'])->paginate($perPage);
    }

    public function trips(User $user, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = FishingTrip::query()
            ->with(['boat', 'crewMembers'])
            ->withCount('catches')
            ->withSum('catches as catch_kg', 'weight_kg')
            ->addSelect(['batch_count' => DB::table('batch_catches')
                ->join('catch_records', 'catch_records.id', '=', 'batch_catches.catch_record_id')
                ->selectRaw('count(distinct batch_catches.fish_batch_id)')
                ->whereColumn('catch_records.fishing_trip_id', 'fishing_trips.id')])
            ->where('organization_id', $user->primaryOrganization()?->id);
        if (! empty($filters['status'])) {
            $query->where('status', strtoupper($filters['status']));
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($builder) => $builder->where('trip_code', 'like', "%{$search}%")->orWhere('general_catch_area', 'like', "%{$search}%"));
        }

        return $this->sort($query, $filters, ['departed_at', 'created_at', 'updated_at'])->paginate($perPage);
    }

    public function catches(User $user, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = CatchRecord::query()->with(['trip', 'species', 'gearType', 'images', 'batches'])->where('organization_id', $user->primaryOrganization()?->id);
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('species', fn ($builder) => $builder->where('common_name', 'like', "%{$search}%")->orWhere('scientific_name', 'like', "%{$search}%"));
        }

        return $this->sort($query, $filters, ['caught_at', 'created_at', 'updated_at'])->paginate($perPage);
    }

    public function batches(User $user, int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = FishBatch::query()->with(['species', 'qrCode'])->where('organization_id', $user->primaryOrganization()?->id);
        if (! empty($filters['status'])) {
            $query->where('status', strtoupper($filters['status']));
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(fn ($builder) => $builder->where('batch_code', 'like', "%{$search}%")->orWhere('product_type', 'like', "%{$search}%"));
        }

        return $this->sort($query, $filters, ['batch_code', 'created_at', 'updated_at'])->paginate($perPage);
    }

    private function sort(Builder $query, array $filters, array $allowed): Builder
    {
        $sort = in_array($filters['sort'] ?? null, $allowed, true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction);
    }

    public function trip(FishingTrip $trip): FishingTrip
    {
        $trip->load(['boat', 'crewMembers', 'catches.species'])->loadCount('catches')->loadSum('catches as catch_kg', 'weight_kg');
        $trip->setAttribute('batch_count', DB::table('batch_catches')
            ->join('catch_records', 'catch_records.id', '=', 'batch_catches.catch_record_id')
            ->where('catch_records.fishing_trip_id', $trip->id)
            ->distinct('batch_catches.fish_batch_id')
            ->count('batch_catches.fish_batch_id'));

        return $trip;
    }

    public function catch(CatchRecord $catch): CatchRecord
    {
        return $catch->load(['trip', 'species', 'gearType', 'images', 'batches']);
    }

    public function batch(FishBatch $batch): FishBatch
    {
        return $batch->load(['species', 'catches', 'qrCode', 'events', 'fishingTrip.boat']);
    }

    public function timeline(FishBatch $batch): LengthAwarePaginator
    {
        return $batch->events()->paginate(50);
    }

    public function qrToken(FishBatch $batch): ?string
    {
        $token = $batch->qrCode()->value('public_token');

        return is_string($token) ? $token : null;
    }
}
