<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Processing\CreateProcessingRecord;
use App\Actions\Processing\UpdateProcessingRecord;
use App\Http\Controllers\Controller;
use App\Http\Requests\Processor\StoreProcessingRecordRequest;
use App\Http\Requests\Processor\UpdateProcessingRecordRequest;
use App\Http\Resources\Processor\ProcessingRecordResource;
use App\Models\ProcessingRecord;
use App\Services\Processing\ProcessingOperationsQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProcessingRecordController extends Controller
{
    public function store(StoreProcessingRecordRequest $request, CreateProcessingRecord $action): JsonResponse
    {
        return ApiResponse::data(new ProcessingRecordResource($action->execute($request->user(), $request->validated())), 201);
    }

    public function show(ProcessingRecord $processingRecord, ProcessingOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $processingRecord);

        return ApiResponse::data(new ProcessingRecordResource($operations->record($processingRecord)));
    }

    public function update(UpdateProcessingRecordRequest $request, ProcessingRecord $processingRecord, UpdateProcessingRecord $action): JsonResponse
    {
        return ApiResponse::data(new ProcessingRecordResource($action->execute($processingRecord, $request->validated())));
    }
}
