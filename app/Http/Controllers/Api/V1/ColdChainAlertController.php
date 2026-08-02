<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\IoT\AcknowledgeColdChainAlert;
use App\Http\Controllers\Controller;
use App\Http\Requests\IoT\AcknowledgeColdChainAlertRequest;
use App\Http\Requests\IoT\TransportAlertFilterRequest;
use App\Http\Resources\IoT\ColdChainAlertResource;
use App\Models\ColdChainAlert;
use App\Models\TransportTrip;
use App\Services\Transport\TransportOperationsQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ColdChainAlertController extends Controller
{
    public function index(TransportAlertFilterRequest $request, TransportTrip $transportTrip, TransportOperationsQuery $operations): JsonResponse
    {
        $filters = $request->validated();
        $page = $operations->alerts($transportTrip, $filters);
        $page->setCollection(ColdChainAlertResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function acknowledge(AcknowledgeColdChainAlertRequest $request, ColdChainAlert $alert, AcknowledgeColdChainAlert $action): JsonResponse
    {
        return ApiResponse::data(new ColdChainAlertResource($action->execute($request->user(), $alert, $request->string('note')->toString() ?: null)));
    }
}
