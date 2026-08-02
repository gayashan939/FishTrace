<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\AuditLogFilterRequest;
use App\Services\Admin\AdminFilterOptions;
use App\Services\Audit\AuditLogQuery;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __invoke(AuditLogFilterRequest $request, AuditLogQuery $logs, AdminFilterOptions $options): View
    {
        abort_unless($request->user()->hasRole('ADMIN'), 403);
        $filters = $request->validated();
        $filters['per_page'] = 50;
        $auditLogs = $logs->paginate($request->user(), $filters);

        return view('admin.audit-logs.index', ['auditLogs' => $auditLogs, 'organizations' => $options->organizations(), 'filters' => $filters]);
    }
}
