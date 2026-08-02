<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AI\AIPredictionDirectoryRequest;
use App\Http\Requests\AI\RequestAIPredictionRequest;
use App\Http\Resources\AI\AIPredictionResource;
use App\Jobs\RequestSpoilagePrediction;
use App\Models\FishBatch;
use App\Services\Processing\ProcessingOperationsQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AIPredictionController extends Controller
{
    public function index(AIPredictionDirectoryRequest $request, FishBatch $batch, ProcessingOperationsQuery $operations): JsonResponse
    {
        $filters = $request->validated();
        $page = $operations->predictions($batch, (int) ($filters['per_page'] ?? 20));
        $page->setCollection(AIPredictionResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function latest(FishBatch $batch, ProcessingOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $batch);

        $prediction = $operations->latestPrediction($batch);

        return ApiResponse::data($prediction === null ? null : new AIPredictionResource($prediction));
    }

    public function store(RequestAIPredictionRequest $request, FishBatch $batch): JsonResponse
    {
        RequestSpoilagePrediction::dispatch($batch->id, $request->user()->id)->afterCommit();

        return ApiResponse::data(['status' => 'QUEUED', 'batch_id' => $batch->id, 'decision_support' => true], 202);
    }
}
