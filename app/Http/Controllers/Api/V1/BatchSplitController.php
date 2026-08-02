<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Batch\SplitFishBatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Processor\ChildBatchDirectoryRequest;
use App\Http\Requests\Processor\SplitBatchRequest;
use App\Http\Resources\Fisher\FishBatchResource;
use App\Models\FishBatch;
use App\Services\Processing\ProcessingOperationsQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class BatchSplitController extends Controller
{
    public function store(SplitBatchRequest $request, FishBatch $batch, SplitFishBatch $action): JsonResponse
    {
        $this->authorize('split', $batch);

        return ApiResponse::data(FishBatchResource::collection($action->execute($request->user(), $batch, $request->validated())), 201);
    }

    public function index(ChildBatchDirectoryRequest $request, FishBatch $batch, ProcessingOperationsQuery $operations): JsonResponse
    {
        $filters = $request->validated();
        $page = $operations->children($batch, (int) ($filters['per_page'] ?? 50));
        $page->setCollection(FishBatchResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }
}
