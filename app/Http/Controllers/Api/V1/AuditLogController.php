<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\AuditLogFilterRequest;
use App\Http\Resources\Audit\AuditLogResource;
use App\Models\AuditLog;
use App\Services\Audit\AuditLogExport;
use App\Services\Audit\AuditLogQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(AuditLogFilterRequest $request, AuditLogQuery $logs): JsonResponse
    {
        $page = $logs->paginate($request->user(), $request->validated());
        $page->setCollection(AuditLogResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function show(AuditLog $auditLog, AuditLogQuery $logs): JsonResponse
    {
        $this->authorize('view', $auditLog);

        return ApiResponse::data(new AuditLogResource($logs->details($auditLog)));
    }

    public function export(AuditLogFilterRequest $request, AuditLogExport $export): StreamedResponse
    {
        return $export->download($request->user(), $request->validated());
    }
}
