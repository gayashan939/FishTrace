<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ManageUserAccess;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserMutationRequest;
use App\Http\Requests\Admin\ResetManagedUserPasswordRequest;
use App\Http\Requests\Admin\StoreManagedUserRequest;
use App\Http\Requests\Admin\UpdateManagedUserRequest;
use App\Http\Requests\Admin\UserDirectoryFilterRequest;
use App\Models\User;
use App\Services\Admin\AccessDirectoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(UserDirectoryFilterRequest $request, AccessDirectoryQuery $directory): View
    {
        $filters = $request->validated();

        return view('admin.users.index', ['users' => $directory->paginatedUsers($filters), 'roles' => $directory->roles(), 'organizations' => $directory->organizationOptions(), 'filters' => $filters]);
    }

    public function create(AccessDirectoryQuery $directory): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create', $this->formData($directory));
    }

    public function store(StoreManagedUserRequest $request, ManageUserAccess $access): RedirectResponse
    {
        $user = $access->create($request->user(), $request->validated());

        return redirect()->route('admin.users.show', $user)->with('success', 'User created and access assigned.');
    }

    public function show(User $managedUser, AccessDirectoryQuery $directory): View
    {
        $this->authorize('view', $managedUser);

        return view('admin.users.show', ['managedUser' => $directory->user($managedUser)]);
    }

    public function edit(User $managedUser, AccessDirectoryQuery $directory): View
    {
        $this->authorize('update', $managedUser);

        return view('admin.users.edit', ['managedUser' => $directory->user($managedUser), ...$this->formData($directory)]);
    }

    public function update(UpdateManagedUserRequest $request, User $managedUser, ManageUserAccess $access): RedirectResponse
    {
        $access->update($request->user(), $managedUser, $request->validated());

        return redirect()->route('admin.users.show', $managedUser)->with('success', 'User access updated and existing sessions revoked.');
    }

    public function activate(AdminUserMutationRequest $request, User $managedUser, ManageUserAccess $access): RedirectResponse
    {
        $access->setStatus($request->user(), $managedUser, UserStatus::ACTIVE);

        return back()->with('success', 'User activated.');
    }

    public function deactivate(AdminUserMutationRequest $request, User $managedUser, ManageUserAccess $access): RedirectResponse
    {
        $access->setStatus($request->user(), $managedUser, UserStatus::DISABLED);

        return back()->with('success', 'User disabled and sessions revoked.');
    }

    public function unlock(AdminUserMutationRequest $request, User $managedUser, ManageUserAccess $access): RedirectResponse
    {
        $access->unlock($request->user(), $managedUser);

        return back()->with('success', 'User account unlocked.');
    }

    public function revokeSessions(AdminUserMutationRequest $request, User $managedUser, ManageUserAccess $access): RedirectResponse
    {
        $count = $access->revokeSessions($request->user(), $managedUser);

        return back()->with('success', "Revoked {$count} active sessions.");
    }

    public function resetPassword(ResetManagedUserPasswordRequest $request, User $managedUser, ManageUserAccess $access): RedirectResponse
    {
        $access->resetPassword($request->user(), $managedUser, $request->validated('password'));

        return back()->with('success', 'Password reset and sessions revoked.');
    }

    private function formData(AccessDirectoryQuery $directory): array
    {
        return ['roles' => $directory->roles(), 'organizations' => $directory->organizationOptions(true)];
    }
}
