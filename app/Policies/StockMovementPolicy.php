<?php

namespace App\Policies;

use App\Models\InventoryLot;
use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function before(User $user): ?bool
    {
        return $user->hasRole('ADMIN') ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasRole('RETAILER') && $user->primaryOrganization() !== null;
    }

    public function view(User $user, StockMovement $movement): bool
    {
        $lot = $movement->inventoryLot;

        return $lot instanceof InventoryLot && $user->hasRole('RETAILER') && $user->primaryOrganization()?->id === $lot->organization_id;
    }
}
