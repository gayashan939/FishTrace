<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\ManageOrganization;
use App\Enums\OrganizationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminOrganizationMutationRequest;
use App\Http\Requests\Admin\OrganizationDirectoryFilterRequest;
use App\Http\Requests\Admin\StoreOrganizationRequest;
use App\Http\Requests\Admin\UpdateOrganizationRequest;
use App\Models\Organization;
use App\Services\Admin\AccessDirectoryQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(OrganizationDirectoryFilterRequest $request, AccessDirectoryQuery $directory): View
    {
        $filters = $request->validated();

        return view('admin.organizations.index', ['organizations' => $directory->paginatedOrganizations($filters), 'types' => OrganizationType::cases(), 'filters' => $filters]);
    }

    public function create(): View
    {
        $this->authorize('create', Organization::class);

        return view('admin.organizations.create', ['types' => OrganizationType::cases()]);
    }

    public function store(StoreOrganizationRequest $request, ManageOrganization $organizations): RedirectResponse
    {
        $organization = $organizations->create($request->user(), $request->validated());

        return redirect()->route('admin.organizations.show', $organization)->with('success', 'Organization created.');
    }

    public function show(Organization $organization, AccessDirectoryQuery $directory): View
    {
        $this->authorize('view', $organization);

        return view('admin.organizations.show', ['organization' => $directory->organization($organization)->load(['users.roles'])]);
    }

    public function edit(Organization $organization): View
    {
        $this->authorize('update', $organization);

        return view('admin.organizations.edit', ['organization' => $organization, 'types' => OrganizationType::cases()]);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization, ManageOrganization $organizations): RedirectResponse
    {
        $organizations->update($request->user(), $organization, $request->validated());

        return redirect()->route('admin.organizations.show', $organization)->with('success', 'Organization updated.');
    }

    public function activate(AdminOrganizationMutationRequest $request, Organization $organization, ManageOrganization $organizations): RedirectResponse
    {
        $organizations->setActive($request->user(), $organization, true);

        return back()->with('success', 'Organization activated.');
    }

    public function deactivate(AdminOrganizationMutationRequest $request, Organization $organization, ManageOrganization $organizations): RedirectResponse
    {
        $organizations->setActive($request->user(), $organization, false);

        return back()->with('success', 'Organization deactivated.');
    }
}
