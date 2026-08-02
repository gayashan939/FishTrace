<?php

namespace App\Services\Fisher;

use App\Models\FishingGearType;
use App\Models\FishSpecies;
use App\Models\LandingSite;

class FisherReferenceData
{
    public function get(): array
    {
        return [
            'species' => FishSpecies::query()->select(['id', 'common_name', 'scientific_name'])->where('is_active', true)->orderBy('common_name')->get(),
            'gear_types' => FishingGearType::query()->select(['id', 'name'])->where('is_active', true)->orderBy('name')->get(),
            'landing_sites' => LandingSite::query()->select(['id', 'name', 'district'])->where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
