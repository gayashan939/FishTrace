<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FileCategory;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ComplianceFilterRequest;
use App\Models\AIPrediction;
use App\Models\BlockchainTransaction;
use App\Models\FileAsset;
use App\Models\FishBatch;
use App\Models\QualityInspection;
use App\Models\ReportExport;
use App\Models\User;
use App\Services\Admin\AdminDirectoryPaginator;
use App\Services\Admin\AdminFilterOptions;
use App\Services\Admin\ComplianceOperationsExport;
use App\Services\Admin\ComplianceOperationsQuery;
use App\Services\Admin\ComplianceOperationsView;
use App\Services\Files\PrivateFileDownload;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ComplianceOperationsController extends Controller
{
    public function __construct(
        private readonly AdminDirectoryPaginator $paginator,
        private readonly AdminFilterOptions $options,
    ) {}

    public function incidents(ComplianceFilterRequest $request, ComplianceOperationsQuery $query): View
    {
        $this->authorize('viewAny', QualityInspection::class);
        $f = $request->validated();

        return view('admin.compliance.incidents.index', ['incidents' => $this->paginator->paginate($query->incidents($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations(), 'filters' => $f]);
    }

    public function incident(Request $request, QualityInspection $inspection, ComplianceOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $inspection);

        return view('admin.compliance.incidents.show', $view->incident($inspection));
    }

    public function recalls(ComplianceFilterRequest $request, ComplianceOperationsQuery $query): View
    {
        $this->authorize('viewAny', FishBatch::class);
        $f = $request->validated();

        return view('admin.compliance.recalls.index', ['batches' => $this->paginator->paginate($query->recalls($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations(), 'filters' => $f]);
    }

    public function evidence(ComplianceFilterRequest $request, ComplianceOperationsQuery $query): View
    {
        $this->authorize('viewAny', FileAsset::class);
        $f = $request->validated();

        return view('admin.compliance.evidence.index', ['files' => $this->paginator->paginate($query->evidence($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations(), 'categories' => FileCategory::cases(), 'filters' => $f]);
    }

    public function download(Request $request, FileAsset $file, PrivateFileDownload $download): BinaryFileResponse|StreamedResponse
    {
        $this->admin($request);
        $this->authorize('view', $file);

        return $download->download($file, 'ADMIN_EVIDENCE_DOWNLOADED', $request->user());
    }

    public function notifications(ComplianceFilterRequest $request, ComplianceOperationsQuery $query): View
    {
        $this->authorize('viewAny', User::class);
        $f = $request->validated();

        return view('admin.compliance.notifications.index', ['notifications' => $this->paginator->paginate($query->notifications($f), (int) ($f['per_page'] ?? 50)), 'organizations' => $this->options->organizations(), 'types' => NotificationType::cases(), 'filters' => $f]);
    }

    public function reports(ComplianceFilterRequest $request, ComplianceOperationsQuery $query): View
    {
        $this->authorize('viewAny', ReportExport::class);
        $f = $request->validated();

        return view('admin.compliance.reports.index', ['exports' => $this->paginator->paginate($query->reports($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations(), 'filters' => $f]);
    }

    public function ai(ComplianceFilterRequest $request, ComplianceOperationsQuery $query): View
    {
        $this->authorize('viewAny', AIPrediction::class);
        $f = $request->validated();

        return view('admin.compliance.ai.index', ['predictions' => $this->paginator->paginate($query->predictions($f), 25, 'predictions_page'), 'failures' => $this->paginator->paginate($query->aiFailures($f), 25, 'failures_page'), 'organizations' => $this->options->organizations(), 'filters' => $f]);
    }

    public function blockchain(ComplianceFilterRequest $request, ComplianceOperationsQuery $query): View
    {
        $this->authorize('viewAny', BlockchainTransaction::class);
        $f = $request->validated();

        return view('admin.compliance.blockchain.index', ['transactions' => $this->paginator->paginate($query->blockchain($f), (int) ($f['per_page'] ?? 25)), 'filters' => $f]);
    }

    public function transaction(Request $request, BlockchainTransaction $transaction, ComplianceOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $transaction);

        return view('admin.compliance.blockchain.show', $view->blockchain($transaction));
    }

    public function exportIncidents(ComplianceFilterRequest $request, ComplianceOperationsExport $export): StreamedResponse
    {
        $this->authorize('viewAny', QualityInspection::class);

        return $export->incidents($request->user(), $request->safe()->except('per_page'));
    }

    public function exportRecalls(ComplianceFilterRequest $request, ComplianceOperationsExport $export): StreamedResponse
    {
        $this->authorize('viewAny', FishBatch::class);

        return $export->recalls($request->user(), $request->safe()->except('per_page'));
    }

    private function admin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('ADMIN'), 403);
    }
}
