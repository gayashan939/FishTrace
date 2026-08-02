<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BatchFilterRequest;
use App\Models\FishBatch;
use App\Services\Admin\AdminDirectoryPaginator;
use App\Services\Admin\AdminFilterOptions;
use App\Services\Admin\BatchOperationsExport;
use App\Services\Admin\BatchOperationsQuery;
use App\Services\Admin\BatchTraceabilityView;
use App\Services\Traceability\QrCodeRenderer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BatchController extends Controller
{
    public function index(BatchFilterRequest $request, BatchOperationsQuery $batches, AdminDirectoryPaginator $paginator, AdminFilterOptions $options): View
    {
        $filters = $request->validated();

        return view('admin.batches.index', ['batches' => $paginator->paginate($batches->build($filters), (int) ($filters['per_page'] ?? 25)), 'organizations' => $options->organizations(), 'species' => $options->species(), 'types' => $options->batchTypes(), 'filters' => $filters]);
    }

    public function show(Request $request, FishBatch $batch, BatchTraceabilityView $traceability): View
    {
        abort_unless($request->user()?->hasRole('ADMIN'), 403);
        $this->authorize('view', $batch);

        return view('admin.batches.show', $traceability->build($batch));
    }

    public function qr(Request $request, FishBatch $batch, BatchOperationsQuery $batches, QrCodeRenderer $renderer): Response
    {
        abort_unless($request->user()?->hasRole('ADMIN'), 403);
        $this->authorize('view', $batch);
        $token = $batches->activeQrToken($batch);
        abort_unless($token !== null, 404);

        return $renderer->trace($token);
    }

    public function export(BatchFilterRequest $request, BatchOperationsExport $export): StreamedResponse
    {
        return $export->download($request->user(), $request->safe()->except('per_page'));
    }
}
