<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\ReportExportDirectoryRequest;
use App\Http\Requests\Reports\ReportFilterRequest;
use App\Http\Requests\Reports\StoreReportExportRequest;
use App\Http\Resources\Files\FileAssetResource;
use App\Http\Resources\Reports\ReportExportResource;
use App\Models\ReportExport;
use App\Services\Reports\ReportCsvDownload;
use App\Services\Reports\ReportDataService;
use App\Services\Reports\ReportExportOperations;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function summary(ReportFilterRequest $request, ReportDataService $reports): JsonResponse
    {
        return ApiResponse::data($reports->summary($request->user(), $request->validated()));
    }

    public function show(ReportFilterRequest $request, string $report, ReportDataService $reports): JsonResponse
    {
        return ApiResponse::data($reports->generate($request->user(), $this->type($report), $request->validated()));
    }

    public function csv(ReportFilterRequest $request, string $report, ReportCsvDownload $download): StreamedResponse
    {
        return $download->download($request->user(), $this->type($report), $request->validated());
    }

    public function print(ReportFilterRequest $request, string $report, ReportDataService $reports): Response
    {
        $data = $reports->generate($request->user(), $this->type($report), $request->validated());

        return response()->view('reports.print', ['report' => $data]);
    }

    public function exports(ReportExportDirectoryRequest $request, ReportExportOperations $exports): JsonResponse
    {
        $filters = $request->validated();
        $page = $exports->directory($request->user(), (int) ($filters['per_page'] ?? 25));
        $page->setCollection(ReportExportResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function storeExport(StoreReportExportRequest $request, ReportExportOperations $exports): JsonResponse
    {
        return ApiResponse::data(new ReportExportResource($exports->queue($request->user(), $request->validated())), 202);
    }

    public function showExport(ReportExport $export, ReportExportOperations $exports): JsonResponse
    {
        $this->authorize('view', $export);
        $export = $exports->details($export);

        return ApiResponse::data([
            'export' => new ReportExportResource($export),
            'file' => $export->file === null ? null : new FileAssetResource($export->file),
        ]);
    }

    private function type(string $report): ReportType
    {
        $type = ReportType::tryFrom($report);
        abort_unless($type !== null, 404, 'Unknown report type.');

        return $type;
    }
}
