<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\AuthenticateAdministrator;
use App\Actions\Admin\LogoutAdministrator;
use App\Enums\AdminAuthenticationResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminLoginRequest;
use App\Http\Requests\Admin\AdminLogoutRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.admin-login');
    }

    public function store(AdminLoginRequest $request, AuthenticateAdministrator $authenticate): RedirectResponse
    {
        $result = $authenticate->execute(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->boolean('remember'),
            $request->session(),
        );

        return match ($result) {
            AdminAuthenticationResult::AUTHENTICATED => redirect()->intended(route('admin.dashboard')),
            AdminAuthenticationResult::INVALID_CREDENTIALS => redirect()->route('admin.login')->withInput($request->only('email'))->with('login_error', 'The credentials are incorrect.'),
            AdminAuthenticationResult::ROLE_REQUIRED => redirect()->route('admin.login')->with('login_error', 'Administrator access is required.'),
        };
    }

    public function destroy(AdminLogoutRequest $request, LogoutAdministrator $logout): RedirectResponse
    {
        $logout->execute($request->user(), $request->session());

        return redirect()->route('admin.login');
    }
}
