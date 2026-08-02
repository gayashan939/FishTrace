<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardMetrics;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardMetrics $metrics): View
    {
        abort_unless($request->user()?->hasRole('ADMIN'), 403);

        return view('admin.dashboard', $metrics->get());
    }
}
