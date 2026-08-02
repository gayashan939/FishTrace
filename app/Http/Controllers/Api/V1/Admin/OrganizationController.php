<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\ManageOrganization;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminOrganizationMutationRequest;
use App\Http\Requests\Admin\OrganizationDirectoryFilterRequest;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Http\Resources\Admin\OrganizationResource;
use App\Models\Organization;
use App\Services\Admin\AccessDirectoryQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    public function index(OrganizationDirectoryFilterRequest $request, AccessDirectoryQuery $directory): JsonResponse
    {
        $filters = $request->validated();
        $page = $directory->paginatedOrganizations($filters);
        $page->setCollection(OrganizationResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function store(StoreOrganizationRequest $request, ManageOrganization $organizations, AccessDirectoryQuery $directory): JsonResponse
    {
        return ApiResponse::data(new OrganizationResource($directory->organization($organizations->create($request->user(), $request->validated()))), 201);
    }

    public function show(Organization $organization, AccessDirectoryQuery $directory): JsonResponse
    {
        $this->authorize('view', $organization);

        return ApiResponse::data(new OrganizationResource($directory->organization($organization)));
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization, ManageOrganization $organizations, AccessDirectoryQuery $directory): JsonResponse
    {
        return ApiResponse::data(new OrganizationResource($directory->organization($organizations->update($request->user(), $organization, $request->validated()))));
    }

    public function activate(AdminOrganizationMutationRequest $request, Organization $organization, ManageOrganization $organizations, AccessDirectoryQuery $directory): JsonResponse
    {
        return ApiResponse::data(new OrganizationResource($directory->organization($organizations->setActive($request->user(), $organization, true))));
    }

    public function deactivate(AdminOrganizationMutationRequest $request, Organization $organization, ManageOrganization $organizations, AccessDirectoryQuery $directory): JsonResponse
    {
        return ApiResponse::data(new OrganizationResource($directory->organization($organizations->setActive($request->user(), $organization, false))));
    }
}
