<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ManageOwnAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeOwnPasswordRequest;
use App\Http\Requests\Admin\RevokeOwnSessionsRequest;
use App\Http\Requests\Admin\UpdateOwnProfileRequest;
use App\Services\Admin\AdminAccountSecurity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()?->hasRole('ADMIN'), 403);

        return view('admin.settings.profile', ['admin' => $request->user()->load(['roles', 'organizations'])]);
    }

    public function update(UpdateOwnProfileRequest $request, ManageOwnAccount $accounts): RedirectResponse
    {
        $accounts->updateProfile($request->user(), $request->validated());

        return back()->with('success', 'Profile updated.');
    }

    public function security(Request $request, AdminAccountSecurity $security): View
    {
        abort_unless($request->user()?->hasRole('ADMIN'), 403);

        return view('admin.settings.security', array_merge(['admin' => $request->user()], $security->get($request->user(), $request->session()->getId())));
    }

    public function password(ChangeOwnPasswordRequest $request, ManageOwnAccount $accounts): RedirectResponse
    {
        $revoked = $accounts->changePassword($request->user(), $request->string('password')->toString(), $request->session()->getId());
        $request->session()->regenerate();

        return back()->with('success', 'Password changed and '.$revoked.' other sessions or tokens revoked.');
    }

    public function revokeOtherSessions(RevokeOwnSessionsRequest $request, ManageOwnAccount $accounts): RedirectResponse
    {
        $revoked = $accounts->revokeOtherSessions($request->user(), $request->session()->getId());

        return back()->with('success', $revoked.' other sessions or tokens revoked.');
    }
}
