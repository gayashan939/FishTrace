<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Processing\ReceiveBatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Processor\ProcessorBatchDirectoryRequest;
use App\Http\Requests\Processor\ReceiveBatchRequest;
use App\Http\Requests\Processor\RejectBatchRequest;
use App\Http\Requests\Processor\ResolveProcessorBatchRequest;
use App\Http\Resources\Fisher\FishBatchResource;
use App\Http\Resources\Processor\BatchIntakeResource;
use App\Models\FishBatch;
use App\Services\Processing\ProcessorBatchQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProcessorBatchController extends Controller
{
    public function dashboard(ProcessorBatchDirectoryRequest $request, ProcessorBatchQuery $batches): JsonResponse
    {
        return ApiResponse::data($batches->dashboard($request->user()->primaryOrganization()?->id));
    }

    public function incoming(ProcessorBatchDirectoryRequest $request, ProcessorBatchQuery $batches): JsonResponse
    {
        $filters = $request->validated();
        $page = $batches->incoming((int) ($filters['per_page'] ?? 20));
        $page->setCollection(FishBatchResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function resolve(ResolveProcessorBatchRequest $request, ProcessorBatchQuery $batches): JsonResponse
    {
        $batch = $batches->resolveIncoming((string) $request->validated('code'));
        abort_if($batch === null, 404, 'No incoming batch matches this QR code.');

        return ApiResponse::data(new FishBatchResource($batch));
    }

    public function history(ProcessorBatchDirectoryRequest $request, ProcessorBatchQuery $batches): JsonResponse
    {
        $filters = $request->validated();
        $page = $batches->history($request->user()->primaryOrganization()?->id, (int) ($filters['per_page'] ?? 20));
        $page->setCollection(BatchIntakeResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function show(ProcessorBatchDirectoryRequest $request, FishBatch $batch, ProcessorBatchQuery $batches): JsonResponse
    {
        $this->authorize('view', $batch);

        return ApiResponse::data(new FishBatchResource($batches->details($batch)));
    }

    public function accept(ReceiveBatchRequest $request, FishBatch $batch, ReceiveBatch $action): JsonResponse
    {
        $this->authorize('receive', $batch);

        return ApiResponse::data(new BatchIntakeResource($action->accept($request->user(), $batch, $request->validated())), 201);
    }

    public function reject(RejectBatchRequest $request, FishBatch $batch, ReceiveBatch $action): JsonResponse
    {
        $this->authorize('receive', $batch);

        return ApiResponse::data(new BatchIntakeResource($action->reject($request->user(), $batch, $request->validated())), 201);
    }
}
