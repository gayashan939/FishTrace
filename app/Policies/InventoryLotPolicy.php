<?php

namespace App\Policies;

use App\Models\InventoryLot;
use App\Models\User;

class InventoryLotPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('RETAILER');
    }

    public function view(User $user, InventoryLot $lot): bool
    {
        return $user->hasRole('RETAILER') && $lot->organization_id === $user->primaryOrganization()?->id;
    }

    public function update(User $user, InventoryLot $lot): bool
    {
        return $this->view($user, $lot);
    }
}
