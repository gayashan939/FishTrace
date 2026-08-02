<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Processing\TransitionProcessingStep;
use App\Http\Controllers\Controller;
use App\Http\Requests\Processor\CompleteProcessingStepRequest;
use App\Http\Requests\Processor\StartProcessingStepRequest;
use App\Http\Resources\Processor\ProcessingStepResource;
use App\Models\ProcessingRecord;
use App\Models\ProcessingStep;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProcessingStepController extends Controller
{
    public function start(StartProcessingStepRequest $request, ProcessingRecord $processingRecord, ProcessingStep $step, TransitionProcessingStep $action): JsonResponse
    {
        return ApiResponse::data(new ProcessingStepResource($action->start($request->user(), $processingRecord, $step)));
    }

    public function complete(CompleteProcessingStepRequest $request, ProcessingRecord $processingRecord, ProcessingStep $step, TransitionProcessingStep $action): JsonResponse
    {
        return ApiResponse::data(new ProcessingStepResource($action->complete($request->user(), $processingRecord, $step, $request->validated())));
    }
}
