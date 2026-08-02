<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Files\StoreBatchDocument;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fisher\BatchDocumentDirectoryRequest;
use App\Http\Requests\Fisher\StoreBatchDocumentRequest;
use App\Http\Resources\Files\FileAssetResource;
use App\Models\FishBatch;
use App\Services\Files\BatchDocumentQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class BatchDocumentController extends Controller
{
    public function index(BatchDocumentDirectoryRequest $request, FishBatch $batch, BatchDocumentQuery $documents): JsonResponse
    {
        $filters = $request->validated();
        $page = $documents->forBatch($batch, (int) ($filters['per_page'] ?? 20));
        $page->setCollection(FileAssetResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function store(StoreBatchDocumentRequest $request, FishBatch $batch, StoreBatchDocument $action): JsonResponse
    {
        return ApiResponse::data(new FileAssetResource($action->execute($request->user(), $batch, $request->validated())), 201);
    }
}
