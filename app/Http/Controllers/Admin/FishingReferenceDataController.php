<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ManageFishingReferenceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FishingReferenceFilterRequest;
use App\Http\Requests\Admin\StoreFishingGearTypeRequest;
use App\Http\Requests\Admin\StoreFishSpeciesRequest;
use App\Http\Requests\Admin\StoreLandingSiteRequest;
use App\Http\Requests\Admin\UpdateFishingGearTypeRequest;
use App\Http\Requests\Admin\UpdateFishSpeciesRequest;
use App\Http\Requests\Admin\UpdateLandingSiteRequest;
use App\Models\FishingGearType;
use App\Models\FishSpecies;
use App\Models\LandingSite;
use App\Services\Admin\FishingReferenceDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FishingReferenceDataController extends Controller
{
    public function species(FishingReferenceFilterRequest $request, FishingReferenceDirectory $directory): View
    {
        $this->authorize('viewAny', FishSpecies::class);
        $filters = $request->validated();

        return view('admin.reference-data.index', ['kind' => 'species', 'records' => $directory->species($filters), 'filters' => $filters]);
    }

    public function createSpecies(): View
    {
        $this->authorize('create', FishSpecies::class);

        return view('admin.reference-data.form', ['kind' => 'species', 'record' => new FishSpecies]);
    }

    public function storeSpecies(StoreFishSpeciesRequest $request, ManageFishingReferenceData $action): RedirectResponse
    {
        $action->createSpecies($request->user(), $request->validated());

        return redirect()->route('admin.reference-data.species.index')->with('success', 'Fish species created.');
    }

    public function editSpecies(FishSpecies $species): View
    {
        $this->authorize('update', $species);

        return view('admin.reference-data.form', ['kind' => 'species', 'record' => $species]);
    }

    public function updateSpecies(UpdateFishSpeciesRequest $request, FishSpecies $species, ManageFishingReferenceData $action): RedirectResponse
    {
        $action->updateSpecies($request->user(), $species, $request->validated());

        return redirect()->route('admin.reference-data.species.index')->with('success', 'Fish species updated.');
    }

    public function gear(FishingReferenceFilterRequest $request, FishingReferenceDirectory $directory): View
    {
        $this->authorize('viewAny', FishingGearType::class);
        $filters = $request->validated();

        return view('admin.reference-data.index', ['kind' => 'gear', 'records' => $directory->gear($filters), 'filters' => $filters]);
    }

    public function createGear(): View
    {
        $this->authorize('create', FishingGearType::class);

        return view('admin.reference-data.form', ['kind' => 'gear', 'record' => new FishingGearType]);
    }

    public function storeGear(StoreFishingGearTypeRequest $request, ManageFishingReferenceData $action): RedirectResponse
    {
        $action->createGear($request->user(), $request->validated());

        return redirect()->route('admin.reference-data.gear.index')->with('success', 'Fishing gear type created.');
    }

    public function editGear(FishingGearType $gear): View
    {
        $this->authorize('update', $gear);

        return view('admin.reference-data.form', ['kind' => 'gear', 'record' => $gear]);
    }

    public function updateGear(UpdateFishingGearTypeRequest $request, FishingGearType $gear, ManageFishingReferenceData $action): RedirectResponse
    {
        $action->updateGear($request->user(), $gear, $request->validated());

        return redirect()->route('admin.reference-data.gear.index')->with('success', 'Fishing gear type updated.');
    }

    public function sites(FishingReferenceFilterRequest $request, FishingReferenceDirectory $directory): View
    {
        $this->authorize('viewAny', LandingSite::class);
        $filters = $request->validated();

        return view('admin.reference-data.index', ['kind' => 'sites', 'records' => $directory->sites($filters), 'filters' => $filters]);
    }

    public function createSite(): View
    {
        $this->authorize('create', LandingSite::class);

        return view('admin.reference-data.form', ['kind' => 'sites', 'record' => new LandingSite]);
    }

    public function storeSite(StoreLandingSiteRequest $request, ManageFishingReferenceData $action): RedirectResponse
    {
        $action->createSite($request->user(), $request->validated());

        return redirect()->route('admin.reference-data.sites.index')->with('success', 'Landing site created.');
    }

    public function editSite(LandingSite $site): View
    {
        $this->authorize('update', $site);

        return view('admin.reference-data.form', ['kind' => 'sites', 'record' => $site]);
    }

    public function updateSite(UpdateLandingSiteRequest $request, LandingSite $site, ManageFishingReferenceData $action): RedirectResponse
    {
        $action->updateSite($request->user(), $site, $request->validated());

        return redirect()->route('admin.reference-data.sites.index')->with('success', 'Landing site updated.');
    }
}
