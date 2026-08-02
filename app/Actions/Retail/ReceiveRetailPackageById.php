<?php

namespace App\Actions\Retail;

use App\Models\InventoryLot;
use App\Models\PackageLabel;
use App\Models\User;

class ReceiveRetailPackageById
{
    public function __construct(private ReceiveRetailPackage $receive) {}

    public function execute(User $user, string $labelId, array $data): InventoryLot
    {
        return $this->receive->execute($user, PackageLabel::query()->findOrFail($labelId), $data);
    }
}
