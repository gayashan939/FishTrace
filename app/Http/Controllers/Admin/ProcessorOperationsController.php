<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProcessorFilterRequest;
use App\Models\BatchIntake;
use App\Models\PackageLabel;
use App\Models\ProcessingRecord;
use App\Models\ProcessorProfile;
use App\Models\QualityInspection;
use App\Services\Admin\AdminDirectoryPaginator;
use App\Services\Admin\AdminFilterOptions;
use App\Services\Admin\ProcessorOperationsExport;
use App\Services\Admin\ProcessorOperationsQuery;
use App\Services\Admin\ProcessorOperationsView;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProcessorOperationsController extends Controller
{
    public function __construct(
        private readonly AdminDirectoryPaginator $paginator,
        private readonly AdminFilterOptions $options,
    ) {}

    public function facilities(ProcessorFilterRequest $request, ProcessorOperationsQuery $query): View
    {
        $this->authorize('viewAny', ProcessorProfile::class);
        $f = $request->validated();

        return view('admin.processor.facilities.index', ['facilities' => $this->paginator->paginate($query->facilities($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('PROCESSOR'), 'filters' => $f]);
    }

    public function facility(Request $request, ProcessorProfile $profile, ProcessorOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $profile);

        return view('admin.processor.facilities.show', $view->facility($profile));
    }

    public function intakes(ProcessorFilterRequest $request, ProcessorOperationsQuery $query): View
    {
        $this->authorize('viewAny', BatchIntake::class);
        $f = $request->validated();

        return view('admin.processor.intakes.index', ['intakes' => $this->paginator->paginate($query->intakes($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('PROCESSOR'), 'filters' => $f]);
    }

    public function intake(Request $request, BatchIntake $intake, ProcessorOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $intake);

        return view('admin.processor.intakes.show', $view->intake($intake));
    }

    public function records(ProcessorFilterRequest $request, ProcessorOperationsQuery $query): View
    {
        $this->authorize('viewAny', ProcessingRecord::class);
        $f = $request->validated();

        return view('admin.processor.records.index', ['records' => $this->paginator->paginate($query->records($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('PROCESSOR'), 'types' => $this->options->processingTypes(), 'filters' => $f]);
    }

    public function record(Request $request, ProcessingRecord $record, ProcessorOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $record);

        return view('admin.processor.records.show', $view->record($record));
    }

    public function inspections(ProcessorFilterRequest $request, ProcessorOperationsQuery $query): View
    {
        $this->authorize('viewAny', QualityInspection::class);
        $f = $request->validated();

        return view('admin.processor.inspections.index', ['inspections' => $this->paginator->paginate($query->inspections($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('PROCESSOR'), 'grades' => $this->options->qualityGrades(), 'filters' => $f]);
    }

    public function inspection(Request $request, QualityInspection $inspection, ProcessorOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $inspection);

        return view('admin.processor.inspections.show', $view->inspection($inspection));
    }

    public function labels(ProcessorFilterRequest $request, ProcessorOperationsQuery $query): View
    {
        $this->authorize('viewAny', PackageLabel::class);
        $f = $request->validated();

        return view('admin.processor.labels.index', ['labels' => $this->paginator->paginate($query->labels($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('PROCESSOR'), 'filters' => $f]);
    }

    public function label(Request $request, PackageLabel $label, ProcessorOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $label);

        return view('admin.processor.labels.show', $view->label($label));
    }

    public function exportRecords(ProcessorFilterRequest $request, ProcessorOperationsExport $export): StreamedResponse
    {
        $this->authorize('viewAny', ProcessingRecord::class);

        return $export->records($request->user(), $request->safe()->except('per_page'));
    }

    public function exportInspections(ProcessorFilterRequest $request, ProcessorOperationsExport $export): StreamedResponse
    {
        $this->authorize('viewAny', QualityInspection::class);

        return $export->inspections($request->user(), $request->safe()->except('per_page'));
    }

    private function admin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('ADMIN'), 403);
    }
}
