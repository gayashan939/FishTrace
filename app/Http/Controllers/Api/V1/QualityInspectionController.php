<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Processing\SubmitQualityInspection;
use App\Http\Controllers\Controller;
use App\Http\Requests\Processor\StoreQualityInspectionRequest;
use App\Http\Resources\Processor\QualityInspectionResource;
use App\Models\QualityInspection;
use App\Services\Processing\ProcessingOperationsQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class QualityInspectionController extends Controller
{
    public function store(StoreQualityInspectionRequest $request, SubmitQualityInspection $action): JsonResponse
    {
        return ApiResponse::data(new QualityInspectionResource($action->execute($request->user(), $request->validated())), 201);
    }

    public function show(QualityInspection $inspection, ProcessingOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $inspection);

        return ApiResponse::data(new QualityInspectionResource($operations->inspection($inspection)));
    }
}
