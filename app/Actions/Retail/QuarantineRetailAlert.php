<?php

namespace App\Actions\Retail;

use App\Actions\IoT\AcknowledgeColdChainAlert;
use App\Enums\InventoryStatus;
use App\Models\ColdChainAlert;
use App\Models\InventoryLot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuarantineRetailAlert
{
    public function __construct(private AdjustRetailInventory $inventory, private AcknowledgeColdChainAlert $acknowledge) {}

    public function execute(User $user, ColdChainAlert $alert, string $reason): array
    {
        return DB::transaction(function () use ($user, $alert, $reason): array {
            $lockedAlert = ColdChainAlert::query()->lockForUpdate()->findOrFail($alert->id);
            abort_if($lockedAlert->status === 'RESOLVED', 409, 'A resolved alert cannot quarantine inventory.');
            $organizationId = $user->primaryOrganization()?->id;
            abort_unless($organizationId !== null && $lockedAlert->fish_batch_id !== null, 403);
            $lots = InventoryLot::query()->where('organization_id', $organizationId)->where('fish_batch_id', $lockedAlert->fish_batch_id)->orderBy('id')->lockForUpdate()->get();
            abort_if($lots->isEmpty(), 404, 'No affected inventory is available.');
            $changed = false;
            foreach ($lots as $lot) {
                if (in_array($lot->getRawOriginal('status'), [InventoryStatus::IN_STOCK->value, InventoryStatus::RESERVED->value], true)) {
                    $this->inventory->markUnavailable($user, $lot, InventoryStatus::RECALLED, $reason);
                    $changed = true;
                }
            }
            abort_unless($changed || $lots->contains(fn (InventoryLot $lot): bool => $lot->getRawOriginal('status') === InventoryStatus::RECALLED->value), 409, 'No saleable inventory remains to quarantine.');
            if ($lockedAlert->status === 'OPEN') {
                $lockedAlert = $this->acknowledge->execute($user, $lockedAlert, $reason);
            }

            return ['alert' => $lockedAlert->fresh(), 'inventory_lots' => InventoryLot::query()->whereKey($lots->modelKeys())->with(['label', 'location'])->get()];
        });
    }
}
