<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Retail\QuarantineRetailAlert;
use App\Actions\Retail\ResolveRetailAlert;
use App\Http\Controllers\Controller;
use App\Http\Requests\Retail\QuarantineRetailAlertRequest;
use App\Http\Requests\Retail\ResolveRetailAlertRequest;
use App\Http\Requests\Retail\RetailAlertFilterRequest;
use App\Http\Resources\Retail\InventoryLotResource;
use App\Http\Resources\Retail\RetailAlertResource;
use App\Models\ColdChainAlert;
use App\Services\Retail\RetailOperationsQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RetailAlertController extends Controller
{
    public function index(RetailAlertFilterRequest $request, RetailOperationsQuery $operations): JsonResponse
    {
        $filters = $request->validated();
        $page = $operations->alerts($request->user(), $filters);
        $page->setCollection(RetailAlertResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function quarantine(QuarantineRetailAlertRequest $request, ColdChainAlert $alert, QuarantineRetailAlert $action): JsonResponse
    {
        $result = $action->execute($request->user(), $alert, $request->validated('reason'));

        return ApiResponse::data([
            'alert' => new RetailAlertResource($result['alert']),
            'inventory_lots' => InventoryLotResource::collection($result['inventory_lots']),
        ]);
    }

    public function resolve(ResolveRetailAlertRequest $request, ColdChainAlert $alert, ResolveRetailAlert $action): JsonResponse
    {
        return ApiResponse::data(new RetailAlertResource($action->execute($request->user(), $alert, $request->validated('note'))));
    }
}
