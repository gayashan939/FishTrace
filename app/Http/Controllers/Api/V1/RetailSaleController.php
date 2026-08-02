<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Retail\RecordRetailSale;
use App\Http\Controllers\Controller;
use App\Http\Requests\Retail\RetailSaleDirectoryRequest;
use App\Http\Requests\Retail\StoreRetailSaleRequest;
use App\Http\Resources\Retail\RetailSaleResource;
use App\Models\RetailSale;
use App\Services\Retail\RetailOperationsQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RetailSaleController extends Controller
{
    public function index(RetailSaleDirectoryRequest $request, RetailOperationsQuery $operations): JsonResponse
    {
        $filters = $request->validated();
        $page = $operations->sales($request->user(), (int) ($filters['per_page'] ?? 25));
        $page->setCollection(RetailSaleResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function store(StoreRetailSaleRequest $request, RecordRetailSale $action): JsonResponse
    {
        return ApiResponse::data(new RetailSaleResource($action->execute($request->user(), $request->validated())), 201);
    }

    public function show(RetailSale $sale, RetailOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $sale);

        return ApiResponse::data(new RetailSaleResource($operations->sale($sale)));
    }
}
