<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\RoleResource;
use App\Models\Role;
use App\Services\Admin\AccessDirectoryQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RoleController extends Controller
{
    public function index(AccessDirectoryQuery $directory): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return ApiResponse::data(RoleResource::collection($directory->roles()));
    }

    public function show(Role $role, AccessDirectoryQuery $directory): JsonResponse
    {
        $this->authorize('view', $role);

        return ApiResponse::data(new RoleResource($directory->role($role)));
    }
}
