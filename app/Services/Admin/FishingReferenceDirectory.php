<?php

namespace App\Services\Admin;

use App\Models\FishingGearType;
use App\Models\FishSpecies;
use App\Models\LandingSite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FishingReferenceDirectory
{
    public function species(array $filters): LengthAwarePaginator
    {
        return FishSpecies::query()
            ->withCount(['catches', 'batches'])
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('common_name', 'like', '%'.$search.'%')->orWhere('scientific_name', 'like', '%'.$search.'%')))
            ->when(array_key_exists('is_active', $filters) && $filters['is_active'] !== null, fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->orderBy($filters['sort'] ?? 'common_name', $filters['direction'] ?? 'asc')
            ->paginate(25)
            ->withQueryString();
    }

    public function gear(array $filters): LengthAwarePaginator
    {
        return FishingGearType::query()
            ->withCount('catches')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->when(array_key_exists('is_active', $filters) && $filters['is_active'] !== null, fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')
            ->paginate(25)
            ->withQueryString();
    }

    public function sites(array $filters): LengthAwarePaginator
    {
        return LandingSite::query()
            ->withCount('trips')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$search.'%')->orWhere('district', 'like', '%'.$search.'%')))
            ->when(array_key_exists('is_active', $filters) && $filters['is_active'] !== null, fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')
            ->paginate(25)
            ->withQueryString();
    }
}
