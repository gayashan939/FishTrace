<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Retail\AdjustRetailInventory;
use App\Actions\Retail\AdjustRetailStockById;
use App\Actions\Retail\ReceiveRetailPackage;
use App\Actions\Retail\ReceiveRetailPackageById;
use App\Enums\InventoryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Retail\AdjustInventoryRequest;
use App\Http\Requests\Retail\MarkInventoryUnavailableRequest;
use App\Http\Requests\Retail\ReceivePackageRequest;
use App\Http\Requests\Retail\RetailDirectoryRequest;
use App\Http\Requests\Retail\RetailInventoryFilterRequest;
use App\Http\Requests\Retail\ResolveRetailLabelRequest;
use App\Http\Requests\Retail\StoreRetailReceiptRequest;
use App\Http\Requests\Retail\StoreStockAdjustmentRequest;
use App\Http\Resources\Retail\InventoryLotResource;
use App\Http\Resources\Retail\PackageLabelResource;
use App\Http\Resources\Retail\RetailReceiptResource;
use App\Models\InventoryLot;
use App\Models\PackageLabel;
use App\Models\RetailReceipt;
use App\Services\Retail\RetailOperationsQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RetailInventoryController extends Controller
{
    public function dashboard(Request $request, RetailOperationsQuery $operations): JsonResponse
    {
        $this->authorize('viewAny', InventoryLot::class);

        return ApiResponse::data($operations->dashboard($request->user()));
    }

    public function incoming(RetailDirectoryRequest $request, RetailOperationsQuery $operations): JsonResponse
    {
        $filters = $request->validated();
        $page = $operations->incoming((int) ($filters['per_page'] ?? 25));
        $page->setCollection(PackageLabelResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function resolveLabel(ResolveRetailLabelRequest $request, RetailOperationsQuery $operations): JsonResponse
    {
        $label = $operations->resolveIncomingLabel((string) $request->validated('code'));
        abort_if($label === null, 404, 'No incoming package matches this QR code.');

        return ApiResponse::data(new PackageLabelResource($label));
    }

    public function receipts(RetailDirectoryRequest $request, RetailOperationsQuery $operations): JsonResponse
    {
        $filters = $request->validated();
        $page = $operations->receipts($request->user(), (int) ($filters['per_page'] ?? 25));
        $page->setCollection(RetailReceiptResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function receipt(RetailReceipt $receipt, RetailOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $receipt);

        return ApiResponse::data(new RetailReceiptResource($operations->receipt($receipt)));
    }

    public function storeReceipt(StoreRetailReceiptRequest $request, ReceiveRetailPackageById $action): JsonResponse
    {
        return ApiResponse::data(new InventoryLotResource($action->execute($request->user(), $request->validated('package_label_id'), $request->safe()->except('package_label_id'))), 201);
    }

    public function receive(ReceivePackageRequest $request, PackageLabel $label, ReceiveRetailPackage $action): JsonResponse
    {
        return ApiResponse::data(new InventoryLotResource($action->execute($request->user(), $label, $request->validated())), 201);
    }

    public function index(RetailInventoryFilterRequest $request, RetailOperationsQuery $operations): JsonResponse
    {
        $page = $operations->inventory($request->user(), $request->validated());
        $page->setCollection(InventoryLotResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function show(InventoryLot $lot, RetailOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $lot);

        return ApiResponse::data(new InventoryLotResource($operations->inventoryLot($lot)));
    }

    public function reserve(AdjustInventoryRequest $request, InventoryLot $lot, AdjustRetailInventory $action): JsonResponse
    {
        return ApiResponse::data(new InventoryLotResource($action->reserve($request->user(), $lot, (int) $request->validated('quantity'))));
    }

    public function release(AdjustInventoryRequest $request, InventoryLot $lot, AdjustRetailInventory $action): JsonResponse
    {
        return ApiResponse::data(new InventoryLotResource($action->release($request->user(), $lot, (int) $request->validated('quantity'))));
    }

    public function recall(MarkInventoryUnavailableRequest $request, InventoryLot $lot, AdjustRetailInventory $action): JsonResponse
    {
        return ApiResponse::data(new InventoryLotResource($action->markUnavailable($request->user(), $lot, InventoryStatus::RECALLED, $request->validated('reason'))));
    }

    public function expire(MarkInventoryUnavailableRequest $request, InventoryLot $lot, AdjustRetailInventory $action): JsonResponse
    {
        return ApiResponse::data(new InventoryLotResource($action->markUnavailable($request->user(), $lot, InventoryStatus::EXPIRED, $request->validated('reason'))));
    }

    public function stockAdjustment(StoreStockAdjustmentRequest $request, AdjustRetailStockById $action): JsonResponse
    {
        return ApiResponse::data(new InventoryLotResource($action->execute($request->user(), $request->validated('inventory_lot_id'), $request->validated('direction'), (int) $request->validated('quantity'), $request->validated('reason'))));
    }
}
