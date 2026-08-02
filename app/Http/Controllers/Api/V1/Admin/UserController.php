<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\ManageUserAccess;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUserMutationRequest;
use App\Http\Requests\Admin\ResetManagedUserPasswordRequest;
use App\Http\Requests\Admin\StoreManagedUserRequest;
use App\Http\Requests\Admin\UpdateManagedUserRequest;
use App\Http\Requests\Admin\UserDirectoryFilterRequest;
use App\Http\Resources\Admin\ManagedUserResource;
use App\Models\User;
use App\Services\Admin\AccessDirectoryQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(UserDirectoryFilterRequest $request, AccessDirectoryQuery $directory): JsonResponse
    {
        $filters = $request->validated();
        $page = $directory->paginatedUsers($filters);
        $page->setCollection(ManagedUserResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function store(StoreManagedUserRequest $request, ManageUserAccess $access, AccessDirectoryQuery $directory): JsonResponse
    {
        return ApiResponse::data(new ManagedUserResource($directory->user($access->create($request->user(), $request->validated()))), 201);
    }

    public function show(User $managedUser, AccessDirectoryQuery $directory): JsonResponse
    {
        $this->authorize('view', $managedUser);

        return ApiResponse::data(new ManagedUserResource($directory->user($managedUser)));
    }

    public function update(UpdateManagedUserRequest $request, User $managedUser, ManageUserAccess $access, AccessDirectoryQuery $directory): JsonResponse
    {
        return ApiResponse::data(new ManagedUserResource($directory->user($access->update($request->user(), $managedUser, $request->validated()))));
    }

    public function activate(AdminUserMutationRequest $request, User $managedUser, ManageUserAccess $access, AccessDirectoryQuery $directory): JsonResponse
    {
        return ApiResponse::data(new ManagedUserResource($directory->user($access->setStatus($request->user(), $managedUser, UserStatus::ACTIVE))));
    }

    public function deactivate(AdminUserMutationRequest $request, User $managedUser, ManageUserAccess $access, AccessDirectoryQuery $directory): JsonResponse
    {
        return ApiResponse::data(new ManagedUserResource($directory->user($access->setStatus($request->user(), $managedUser, UserStatus::DISABLED))));
    }

    public function unlock(AdminUserMutationRequest $request, User $managedUser, ManageUserAccess $access, AccessDirectoryQuery $directory): JsonResponse
    {
        return ApiResponse::data(new ManagedUserResource($directory->user($access->unlock($request->user(), $managedUser))));
    }

    public function revokeSessions(AdminUserMutationRequest $request, User $managedUser, ManageUserAccess $access): JsonResponse
    {
        return ApiResponse::data(['revoked_session_count' => $access->revokeSessions($request->user(), $managedUser)]);
    }

    public function resetPassword(ResetManagedUserPasswordRequest $request, User $managedUser, ManageUserAccess $access): JsonResponse
    {
        $access->resetPassword($request->user(), $managedUser, $request->validated('password'));

        return ApiResponse::data(['password_reset' => true, 'sessions_revoked' => true]);
    }
}
