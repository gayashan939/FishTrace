<?php

namespace App\Actions\Admin;

use App\Models\FishingGearType;
use App\Models\FishSpecies;
use App\Models\LandingSite;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ManageFishingReferenceData
{
    public function __construct(private AuditLogger $audit) {}

    public function createSpecies(User $actor, array $attributes): FishSpecies
    {
        return $this->create($actor, new FishSpecies, $attributes, 'FISH_SPECIES_CREATED');
    }

    public function updateSpecies(User $actor, FishSpecies $species, array $attributes): FishSpecies
    {
        return $this->update($actor, $species, $attributes, 'FISH_SPECIES_UPDATED');
    }

    public function createGear(User $actor, array $attributes): FishingGearType
    {
        return $this->create($actor, new FishingGearType, $attributes, 'FISHING_GEAR_CREATED');
    }

    public function updateGear(User $actor, FishingGearType $gear, array $attributes): FishingGearType
    {
        return $this->update($actor, $gear, $attributes, 'FISHING_GEAR_UPDATED');
    }

    public function createSite(User $actor, array $attributes): LandingSite
    {
        return $this->create($actor, new LandingSite, $attributes, 'LANDING_SITE_CREATED');
    }

    public function updateSite(User $actor, LandingSite $site, array $attributes): LandingSite
    {
        return $this->update($actor, $site, $attributes, 'LANDING_SITE_UPDATED');
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    private function create(User $actor, Model $model, array $attributes, string $event): Model
    {
        return DB::transaction(function () use ($actor, $model, $attributes, $event): Model {
            $model->fill($attributes)->save();
            $this->audit->record($event, $model, null, $model->getAttributes(), $actor);

            return $model;
        });
    }

    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @return TModel
     */
    private function update(User $actor, Model $model, array $attributes, string $event): Model
    {
        return DB::transaction(function () use ($actor, $model, $attributes, $event): Model {
            $old = $model->getAttributes();
            $model->fill($attributes)->save();
            $this->audit->record($event, $model, $old, $model->getAttributes(), $actor);

            return $model->fresh() ?? $model;
        });
    }
}
