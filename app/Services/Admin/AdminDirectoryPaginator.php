<?php

namespace App\Services\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminDirectoryPaginator
{
    /**
     * @param  Builder<*>  $query
     */
    public function paginate(Builder $query, int $perPage, string $pageName = 'page'): LengthAwarePaginator
    {
        return $query->paginate($perPage, ['*'], $pageName)->withQueryString();
    }
}
