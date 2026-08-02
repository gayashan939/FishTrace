<?php

namespace App\Policies;

use App\Models\ColdChainAlert;
use App\Models\InventoryLot;
use App\Models\User;

class ColdChainAlertPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('TRANSPORTER') || $user->hasRole('INSPECTOR');
    }

    public function view(User $user, ColdChainAlert $alert): bool
    {
        return $alert->trip()->where('organization_id', $user->primaryOrganization()?->id)->exists();
    }

    public function viewForRetailer(User $user, ColdChainAlert $alert): bool
    {
        return $user->hasRole('RETAILER')
            && $alert->fish_batch_id !== null
            && InventoryLot::query()->where('fish_batch_id', $alert->fish_batch_id)->where('organization_id', $user->primaryOrganization()?->id)->exists();
    }

    public function manageForRetailer(User $user, ColdChainAlert $alert): bool
    {
        return $this->viewForRetailer($user, $alert);
    }
}
