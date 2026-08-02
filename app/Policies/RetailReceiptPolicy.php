<?php

namespace App\Policies;

use App\Models\RetailReceipt;
use App\Models\User;

class RetailReceiptPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('RETAILER') && $user->primaryOrganization() !== null;
    }

    public function view(User $user, RetailReceipt $receipt): bool
    {
        return $user->hasRole('RETAILER') && $user->primaryOrganization()?->id === $receipt->retailer_organization_id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('RETAILER') && $user->primaryOrganization() !== null;
    }
}
