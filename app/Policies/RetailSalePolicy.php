<?php

namespace App\Policies;

use App\Models\RetailSale;
use App\Models\User;

class RetailSalePolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('RETAILER');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('RETAILER') && $user->primaryOrganization() !== null;
    }

    public function view(User $user, RetailSale $sale): bool
    {
        return $user->hasRole('RETAILER') && $sale->organization_id === $user->primaryOrganization()?->id;
    }
}
