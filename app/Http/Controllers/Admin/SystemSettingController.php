<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\UpdateSystemSettings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingsRequest;
use App\Models\SystemSetting;
use App\Services\Settings\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function edit(SystemSettings $settings): View
    {
        $this->authorize('viewAny', SystemSetting::class);

        return view('admin.settings.system', ['settings' => $settings->all()]);
    }

    public function update(UpdateSystemSettingsRequest $request, UpdateSystemSettings $action): RedirectResponse
    {
        $action->execute($request->user(), $request->validated());

        return back()->with('success', 'System settings updated.');
    }
}
