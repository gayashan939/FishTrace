<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportFilterRequest;
use App\Services\Reports\ReportDataService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RetailReportController extends Controller
{
    public function summary(ReportFilterRequest $request, ReportDataService $reports): JsonResponse
    {
        return ApiResponse::data($reports->summary($request->user(), $request->validated()));
    }

    public function sales(ReportFilterRequest $request, ReportDataService $reports): JsonResponse
    {
        return ApiResponse::data($reports->generate($request->user(), ReportType::SALES, $request->validated()));
    }

    public function inventory(ReportFilterRequest $request, ReportDataService $reports): JsonResponse
    {
        return ApiResponse::data($reports->generate($request->user(), ReportType::INVENTORY, $request->validated()));
    }
}
