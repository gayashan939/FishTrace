<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FishingFilterRequest;
use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\FishingTrip;
use App\Models\User;
use App\Services\Admin\AdminDirectoryPaginator;
use App\Services\Admin\AdminFilterOptions;
use App\Services\Admin\FishingOperationsExport;
use App\Services\Admin\FishingOperationsQuery;
use App\Services\Admin\FishingOperationsView;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FishingOperationsController extends Controller
{
    public function __construct(
        private readonly AdminDirectoryPaginator $paginator,
        private readonly AdminFilterOptions $options,
    ) {}

    public function fishers(FishingFilterRequest $request, FishingOperationsQuery $operations): View
    {
        $this->authorize('viewAny', User::class);
        $filters = $request->validated();

        return view('admin.fishing.fishers.index', ['fishers' => $this->paginator->paginate($operations->fishers($filters), (int) ($filters['per_page'] ?? 25)), 'organizations' => $this->options->organizations('FISHER'), 'filters' => $filters]);
    }

    public function fisher(Request $request, User $fisher, FishingOperationsView $operations): View
    {
        $this->admin($request);
        $this->authorize('view', $fisher);

        return view('admin.fishing.fishers.show', $operations->fisher($fisher));
    }

    public function boats(FishingFilterRequest $request, FishingOperationsQuery $operations): View
    {
        $this->authorize('viewAny', Boat::class);
        $filters = $request->validated();

        return view('admin.fishing.boats.index', ['boats' => $this->paginator->paginate($operations->boats($filters), (int) ($filters['per_page'] ?? 25)), 'organizations' => $this->options->organizations('FISHER'), 'fishers' => $this->options->usersByRole('FISHER'), 'filters' => $filters]);
    }

    public function boat(Request $request, Boat $boat, FishingOperationsView $operations): View
    {
        $this->admin($request);
        $this->authorize('view', $boat);

        return view('admin.fishing.boats.show', $operations->boat($boat));
    }

    public function trips(FishingFilterRequest $request, FishingOperationsQuery $operations): View
    {
        $this->authorize('viewAny', FishingTrip::class);
        $filters = $request->validated();

        return view('admin.fishing.trips.index', ['trips' => $this->paginator->paginate($operations->trips($filters), (int) ($filters['per_page'] ?? 25)), 'organizations' => $this->options->organizations('FISHER'), 'fishers' => $this->options->usersByRole('FISHER'), 'boats' => $this->options->boats(), 'filters' => $filters]);
    }

    public function trip(Request $request, FishingTrip $trip, FishingOperationsView $operations): View
    {
        $this->admin($request);
        $this->authorize('view', $trip);

        return view('admin.fishing.trips.show', $operations->trip($trip));
    }

    public function catches(FishingFilterRequest $request, FishingOperationsQuery $operations): View
    {
        $this->authorize('viewAny', CatchRecord::class);
        $filters = $request->validated();

        return view('admin.fishing.catches.index', ['catches' => $this->paginator->paginate($operations->catches($filters), (int) ($filters['per_page'] ?? 25)), 'organizations' => $this->options->organizations('FISHER'), 'fishers' => $this->options->usersByRole('FISHER'), 'species' => $this->options->species(), 'filters' => $filters]);
    }

    public function catch(Request $request, CatchRecord $catchRecord, FishingOperationsView $operations): View
    {
        $this->admin($request);
        $this->authorize('view', $catchRecord);

        return view('admin.fishing.catches.show', $operations->catch($catchRecord));
    }

    public function exportTrips(FishingFilterRequest $request, FishingOperationsExport $export): StreamedResponse
    {
        $this->authorize('viewAny', FishingTrip::class);

        return $export->trips($request->user(), $request->safe()->except('per_page'));
    }

    public function exportCatches(FishingFilterRequest $request, FishingOperationsExport $export): StreamedResponse
    {
        $this->authorize('viewAny', CatchRecord::class);

        return $export->catches($request->user(), $request->safe()->except('per_page'));
    }

    private function admin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('ADMIN'), 403);
    }
}
