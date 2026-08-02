<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Batch\CreateFishBatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fisher\FisherDirectoryRequest;
use App\Http\Requests\Fisher\StoreBatchRequest;
use App\Http\Resources\Fisher\FishBatchResource;
use App\Http\Resources\Fisher\TraceabilityEventResource;
use App\Models\FishBatch;
use App\Services\Fisher\FisherOperationsQuery;
use App\Services\Traceability\QrCodeRenderer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class FishBatchController extends Controller
{
    public function index(FisherDirectoryRequest $request, FisherOperationsQuery $operations): JsonResponse
    {
        $this->authorize('viewAny', FishBatch::class);
        $filters = $request->validated();
        $page = $operations->batches($request->user(), (int) ($filters['per_page'] ?? 20));
        $page->setCollection(FishBatchResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function store(StoreBatchRequest $request, CreateFishBatch $action): JsonResponse
    {
        return ApiResponse::data(new FishBatchResource($action->execute($request->user(), $request->validated())), 201);
    }

    public function show(FishBatch $batch, FisherOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $batch);

        return ApiResponse::data(new FishBatchResource($operations->batch($batch)));
    }

    public function timeline(FishBatch $batch, FisherOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $batch);

        $page = $operations->timeline($batch);
        $page->setCollection(TraceabilityEventResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function qr(FishBatch $batch, FisherOperationsQuery $operations, QrCodeRenderer $renderer): Response
    {
        $this->authorize('view', $batch);
        $token = $operations->qrToken($batch);
        abort_unless(is_string($token), 404);

        return $renderer->trace($token);
    }
}
