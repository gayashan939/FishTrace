<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\Admin\AccessDirectoryQuery;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __invoke(AccessDirectoryQuery $directory): View
    {
        $this->authorize('viewAny', Role::class);

        return view('admin.roles.index', ['roles' => $directory->roles()]);
    }
}
