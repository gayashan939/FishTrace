<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RetailFilterRequest;
use App\Models\InventoryLot;
use App\Models\RetailLocation;
use App\Models\RetailReceipt;
use App\Models\RetailSale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\Admin\AdminDirectoryPaginator;
use App\Services\Admin\AdminFilterOptions;
use App\Services\Admin\RetailOperationsExport;
use App\Services\Admin\RetailOperationsQuery;
use App\Services\Admin\RetailOperationsView;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RetailOperationsController extends Controller
{
    public function __construct(
        private readonly AdminDirectoryPaginator $paginator,
        private readonly AdminFilterOptions $options,
    ) {}

    public function retailers(RetailFilterRequest $request, RetailOperationsQuery $query): View
    {
        $this->authorize('viewAny', User::class);
        $f = $request->validated();

        return view('admin.retail.retailers.index', ['retailers' => $this->paginator->paginate($query->retailers($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('RETAILER'), 'filters' => $f]);
    }

    public function retailer(Request $request, User $retailer, RetailOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $retailer);

        return view('admin.retail.retailers.show', $view->retailer($retailer));
    }

    public function locations(RetailFilterRequest $request, RetailOperationsQuery $query): View
    {
        $this->authorize('viewAny', RetailLocation::class);
        $f = $request->validated();

        return view('admin.retail.locations.index', ['locations' => $this->paginator->paginate($query->locations($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('RETAILER'), 'filters' => $f]);
    }

    public function location(Request $request, RetailLocation $location, RetailOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $location);

        return view('admin.retail.locations.show', $view->location($location));
    }

    public function receipts(RetailFilterRequest $request, RetailOperationsQuery $query): View
    {
        $this->authorize('viewAny', RetailReceipt::class);
        $f = $request->validated();

        return view('admin.retail.receipts.index', ['receipts' => $this->paginator->paginate($query->receipts($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('RETAILER'), 'locations' => $this->options->retailLocations(), 'filters' => $f]);
    }

    public function receipt(Request $request, RetailReceipt $receipt, RetailOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $receipt);

        return view('admin.retail.receipts.show', $view->receipt($receipt));
    }

    public function inventory(RetailFilterRequest $request, RetailOperationsQuery $query): View
    {
        $this->authorize('viewAny', InventoryLot::class);
        $f = $request->validated();

        return view('admin.retail.inventory.index', ['lots' => $this->paginator->paginate($query->inventory($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('RETAILER'), 'locations' => $this->options->retailLocations(), 'filters' => $f]);
    }

    public function lot(Request $request, InventoryLot $lot, RetailOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $lot);

        return view('admin.retail.inventory.show', $view->inventory($lot));
    }

    public function movements(RetailFilterRequest $request, RetailOperationsQuery $query): View
    {
        $this->authorize('viewAny', StockMovement::class);
        $f = $request->validated();

        return view('admin.retail.movements.index', ['movements' => $this->paginator->paginate($query->movements($f), (int) ($f['per_page'] ?? 50)), 'organizations' => $this->options->organizations('RETAILER'), 'locations' => $this->options->retailLocations(), 'filters' => $f]);
    }

    public function sales(RetailFilterRequest $request, RetailOperationsQuery $query): View
    {
        $this->authorize('viewAny', RetailSale::class);
        $f = $request->validated();

        return view('admin.retail.sales.index', ['sales' => $this->paginator->paginate($query->sales($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('RETAILER'), 'locations' => $this->options->retailLocations(), 'filters' => $f]);
    }

    public function sale(Request $request, RetailSale $sale, RetailOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $sale);

        return view('admin.retail.sales.show', $view->sale($sale));
    }

    public function risks(RetailFilterRequest $request, RetailOperationsQuery $query): View
    {
        $this->authorize('viewAny', InventoryLot::class);
        $f = $request->validated();

        return view('admin.retail.risks.index', ['lots' => $this->paginator->paginate($query->risks($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('RETAILER'), 'filters' => $f]);
    }

    public function exportInventory(RetailFilterRequest $request, RetailOperationsExport $export): StreamedResponse
    {
        $this->authorize('viewAny', InventoryLot::class);

        return $export->inventory($request->user(), $request->safe()->except('per_page'));
    }

    public function exportSales(RetailFilterRequest $request, RetailOperationsExport $export): StreamedResponse
    {
        $this->authorize('viewAny', RetailSale::class);

        return $export->sales($request->user(), $request->safe()->except('per_page'));
    }

    private function admin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('ADMIN'), 403);
    }
}
