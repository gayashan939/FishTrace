<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProcessingType;
use App\Models\QualityGrade;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProcessorReferenceDataController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasRole('PROCESSOR') || $request->user()?->hasRole('INSPECTOR'), 403);

        return ApiResponse::data([
            'processing_types' => ProcessingType::query()->select(['id', 'name'])->where('is_active', true)->orderBy('name')->get(),
            'quality_grades' => QualityGrade::query()->select(['id', 'code', 'name', 'rank'])->where('is_active', true)->orderBy('rank')->get(),
        ]);
    }
}
