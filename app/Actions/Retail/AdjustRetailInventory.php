<?php

namespace App\Actions\Retail;

use App\Enums\InventoryStatus;
use App\Enums\NotificationType;
use App\Enums\StockMovementType;
use App\Models\FishBatch;
use App\Models\InventoryLot;
use App\Models\PackageLabel;
use App\Models\User;
use App\Services\Notifications\OperationalNotifier;
use Illuminate\Support\Facades\DB;

class AdjustRetailInventory
{
    public function __construct(private OperationalNotifier $notifier) {}

    public function reserve(User $user, InventoryLot $lot, int $quantity): InventoryLot
    {
        return $this->adjust($user, $lot, $quantity, true);
    }

    public function release(User $user, InventoryLot $lot, int $quantity): InventoryLot
    {
        return $this->adjust($user, $lot, $quantity, false);
    }

    public function markUnavailable(User $user, InventoryLot $lot, InventoryStatus $status, string $reason): InventoryLot
    {
        abort_unless(in_array($status, [InventoryStatus::RECALLED, InventoryStatus::EXPIRED], true), 422);

        return DB::transaction(function () use ($user, $lot, $status, $reason): InventoryLot {
            $locked = InventoryLot::query()->lockForUpdate()->findOrFail($lot->id);
            abort_unless($locked->organization_id === $user->primaryOrganization()?->id, 403);
            abort_if(in_array($locked->getRawOriginal('status'), [InventoryStatus::SOLD_OUT->value, InventoryStatus::RECALLED->value, InventoryStatus::EXPIRED->value], true), 409, 'This inventory lot cannot be changed.');
            $quantity = $locked->available_packages + $locked->reserved_packages;
            $locked->update(['status' => $status->value]);
            $locked->movements()->create(['actor_id' => $user->id, 'type' => $status === InventoryStatus::RECALLED ? StockMovementType::RECALLED : StockMovementType::EXPIRED, 'quantity' => $quantity, 'resulting_available' => $locked->available_packages, 'resulting_reserved' => $locked->reserved_packages, 'resulting_sold' => $locked->sold_packages, 'reason' => $reason, 'occurred_at' => now()]);
            $batch = FishBatch::findOrFail($locked->fish_batch_id);
            $label = PackageLabel::findOrFail($locked->package_label_id);
            $batch->events()->create(['organization_id' => $locked->organization_id, 'actor_id' => $user->id, 'event_type' => 'RETAIL_'.$status->value, 'title' => $status === InventoryStatus::RECALLED ? 'Retail package recalled' : 'Retail package expired', 'public_data' => ['label_code' => $label->label_code], 'occurred_at' => now()]);
            if ($status === InventoryStatus::RECALLED) {
                $this->notifier->organization($locked->organization_id, NotificationType::RECALL, 'Inventory recalled', $label->label_code.' was quarantined.', ['batch_id' => $batch->id, 'inventory_lot_id' => $locked->id, 'reason' => $reason]);
            }

            return $locked->load(['label', 'batch.species', 'location', 'movements']);
        });
    }

    public function adjustStock(User $user, InventoryLot $lot, string $direction, int $quantity, string $reason): InventoryLot
    {
        return DB::transaction(function () use ($user, $lot, $direction, $quantity, $reason): InventoryLot {
            $locked = InventoryLot::query()->lockForUpdate()->findOrFail($lot->id);
            abort_unless($locked->organization_id === $user->primaryOrganization()?->id, 403);
            abort_unless(in_array($locked->getRawOriginal('status'), [InventoryStatus::IN_STOCK->value, InventoryStatus::RESERVED->value, InventoryStatus::SOLD_OUT->value], true), 409, 'This inventory lot is not adjustable.');
            if ($direction === 'ADD') {
                $locked->total_packages += $quantity;
                $locked->available_packages += $quantity;
                $movement = StockMovementType::ADJUSTED_IN;
            } else {
                abort_if($quantity > $locked->available_packages, 422, 'An adjustment cannot remove reserved, sold, or unavailable packages.');
                $locked->total_packages -= $quantity;
                $locked->available_packages -= $quantity;
                $movement = StockMovementType::ADJUSTED_OUT;
            }
            $status = $locked->available_packages === 0 && $locked->reserved_packages === 0
                ? InventoryStatus::SOLD_OUT
                : ($locked->reserved_packages > 0 ? InventoryStatus::RESERVED : InventoryStatus::IN_STOCK);
            $locked->update(['total_packages' => $locked->total_packages, 'available_packages' => $locked->available_packages, 'status' => $status->value]);
            $locked->movements()->create(['actor_id' => $user->id, 'type' => $movement, 'quantity' => $quantity, 'resulting_available' => $locked->available_packages, 'resulting_reserved' => $locked->reserved_packages, 'resulting_sold' => $locked->sold_packages, 'reason' => $reason, 'occurred_at' => now()]);

            return $locked->load(['label', 'batch.species', 'location', 'movements']);
        });
    }

    private function adjust(User $user, InventoryLot $lot, int $quantity, bool $reserve): InventoryLot
    {
        return DB::transaction(function () use ($user, $lot, $quantity, $reserve): InventoryLot {
            $locked = InventoryLot::query()->lockForUpdate()->findOrFail($lot->id);
            abort_unless($locked->organization_id === $user->primaryOrganization()?->id, 403);
            abort_unless(in_array($locked->getRawOriginal('status'), [InventoryStatus::IN_STOCK->value, InventoryStatus::RESERVED->value], true), 409, 'This inventory lot is not adjustable.');
            if ($reserve) {
                abort_if($quantity > $locked->available_packages, 422, 'Insufficient available packages.');
                $locked->available_packages -= $quantity;
                $locked->reserved_packages += $quantity;
                $type = StockMovementType::RESERVED;
            } else {
                abort_if($quantity > $locked->reserved_packages, 422, 'Insufficient reserved packages.');
                $locked->reserved_packages -= $quantity;
                $locked->available_packages += $quantity;
                $type = StockMovementType::RELEASED;
            }
            $locked->update(['status' => ($locked->reserved_packages > 0 ? InventoryStatus::RESERVED : InventoryStatus::IN_STOCK)->value, 'available_packages' => $locked->available_packages, 'reserved_packages' => $locked->reserved_packages]);
            $locked->movements()->create(['actor_id' => $user->id, 'type' => $type, 'quantity' => $quantity, 'resulting_available' => $locked->available_packages, 'resulting_reserved' => $locked->reserved_packages, 'resulting_sold' => $locked->sold_packages, 'occurred_at' => now()]);

            return $locked->load(['label', 'batch.species', 'location', 'movements']);
        });
    }
}
