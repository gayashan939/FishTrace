<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpdateAlertRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAlertRulesRequest;
use App\Models\AlertRuleConfig;
use App\Services\IoT\AlertRuleResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AlertRuleController extends Controller
{
    public function index(AlertRuleResolver $rules): View
    {
        $this->authorize('viewAny', AlertRuleConfig::class);

        return view('admin.settings.alert-rules', ['rules' => $rules->globalRules()]);
    }

    public function update(UpdateAlertRulesRequest $request, UpdateAlertRules $action): RedirectResponse
    {
        $action->execute($request->user(), $request->validated('rules'));

        return back()->with('success', 'Cold-chain alert rules updated.');
    }
}
