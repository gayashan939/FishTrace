<?php

namespace App\Actions\Retail;

use App\Models\InventoryLot;
use App\Models\User;

class AdjustRetailStockById
{
    public function __construct(private AdjustRetailInventory $inventory) {}

    public function execute(User $user, string $lotId, string $direction, int $quantity, string $reason): InventoryLot
    {
        return $this->inventory->adjustStock($user, InventoryLot::query()->findOrFail($lotId), $direction, $quantity, $reason);
    }
}
