<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fisher\CreateCatchRecord;
use App\Actions\Fisher\DeleteCatchRecord;
use App\Actions\Fisher\UpdateCatchRecord;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fisher\DeleteCatchRequest;
use App\Http\Requests\Fisher\FisherDirectoryRequest;
use App\Http\Requests\Fisher\StoreCatchRequest;
use App\Http\Requests\Fisher\UpdateCatchRequest;
use App\Http\Resources\Fisher\CatchRecordResource;
use App\Models\CatchRecord;
use App\Services\Fisher\FisherOperationsQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CatchRecordController extends Controller
{
    public function index(FisherDirectoryRequest $request, FisherOperationsQuery $operations): JsonResponse
    {
        $this->authorize('viewAny', CatchRecord::class);
        $filters = $request->validated();
        $page = $operations->catches($request->user(), (int) ($filters['per_page'] ?? 20));
        $page->setCollection(CatchRecordResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function store(StoreCatchRequest $request, CreateCatchRecord $action): JsonResponse
    {
        return ApiResponse::data(new CatchRecordResource($action->execute($request->user(), $request->validated())), 201);
    }

    public function show(CatchRecord $catchRecord, FisherOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $catchRecord);

        return ApiResponse::data(new CatchRecordResource($operations->catch($catchRecord)));
    }

    public function update(UpdateCatchRequest $request, CatchRecord $catchRecord, UpdateCatchRecord $action): JsonResponse
    {
        return ApiResponse::data(new CatchRecordResource($action->execute($request->user(), $catchRecord, $request->validated())));
    }

    public function destroy(DeleteCatchRequest $request, CatchRecord $catchRecord, DeleteCatchRecord $action): Response
    {
        $action->execute($request->user(), $catchRecord);

        return response()->noContent();
    }
}
