<?php

namespace App\Actions\Retail;

use App\Enums\BatchStatus;
use App\Enums\InventoryStatus;
use App\Enums\RetailSaleStatus;
use App\Enums\StockMovementType;
use App\Models\FishBatch;
use App\Models\InventoryLot;
use App\Models\Organization;
use App\Models\RetailLocation;
use App\Models\RetailSale;
use App\Models\RetailSaleItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordRetailSale
{
    public function execute(User $user, array $data): RetailSale
    {
        $fingerprint = $this->fingerprint($data);

        return DB::transaction(function () use ($user, $data, $fingerprint): RetailSale {
            $organization = $user->primaryOrganization();
            abort_unless($organization !== null, 403);
            Organization::query()->lockForUpdate()->findOrFail($organization->id);
            $existing = RetailSale::query()->where('organization_id', $organization->id)->where('client_reference', $data['client_reference'])->first();
            if ($existing !== null) {
                $existingFingerprint = $existing->request_fingerprint ?? $this->fingerprint([
                    'retail_location_id' => $existing->retail_location_id,
                    'items' => $existing->items()->get()->map(fn (RetailSaleItem $item): array => ['inventory_lot_id' => $item->inventory_lot_id, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price])->all(),
                ]);
                abort_unless(hash_equals($existingFingerprint, $fingerprint), 409, 'This client reference was already used with a different sale payload.');
                if ($existing->request_fingerprint === null) {
                    $existing->update(['request_fingerprint' => $fingerprint]);
                }

                return $existing->load(['location', 'items.inventoryLot.label', 'items.inventoryLot.batch.species']);
            }
            $location = RetailLocation::query()->whereKey($data['retail_location_id'])->where('organization_id', $organization->id)->where('is_active', true)->first();
            abort_unless($location !== null, 403, 'The retail location does not belong to your organization or is inactive.');
            $requested = collect($data['items'])->keyBy('inventory_lot_id');
            $lots = InventoryLot::query()->whereIn('id', $requested->keys()->sort()->values())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            abort_unless($lots->count() === $requested->count(), 422, 'One or more inventory lots do not exist.');

            $subtotal = 0.0;
            foreach ($requested as $lotId => $item) {
                $lot = $lots->get($lotId);
                abort_unless($lot !== null && $lot->organization_id === $organization->id && $lot->retail_location_id === $location->id, 403, 'Every inventory lot must belong to the selected location.');
                abort_unless(in_array($lot->getRawOriginal('status'), [InventoryStatus::IN_STOCK->value, InventoryStatus::RESERVED->value], true), 409, 'An inventory lot is unavailable for sale.');
                abort_if($lot->expires_at !== null && Carbon::parse($lot->expires_at)->isPast(), 409, 'An inventory lot has expired.');
                abort_if((int) $item['quantity'] > $lot->available_packages, 422, 'Insufficient available packages for this sale.');
                $subtotal += round((int) $item['quantity'] * (float) $item['unit_price'], 2);
            }
            $subtotal = round($subtotal, 2);
            $sale = RetailSale::create([
                'organization_id' => $organization->id,
                'retail_location_id' => $location->id,
                'sold_by' => $user->id,
                'client_reference' => $data['client_reference'],
                'request_fingerprint' => $fingerprint,
                'receipt_number' => 'FTS-'.now()->format('Ymd').'-'.Str::upper(Str::random(10)),
                'status' => RetailSaleStatus::COMPLETED,
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'sold_at' => now(),
            ]);

            foreach ($requested as $lotId => $item) {
                $lot = $lots->get($lotId);
                abort_unless($lot !== null, 422);
                $quantity = (int) $item['quantity'];
                $lineTotal = round($quantity * (float) $item['unit_price'], 2);
                $sale->items()->create(['inventory_lot_id' => $lot->id, 'quantity' => $quantity, 'unit_price' => $item['unit_price'], 'line_total' => $lineTotal]);
                $lot->available_packages -= $quantity;
                $lot->sold_packages += $quantity;
                $status = $lot->available_packages === 0 && $lot->reserved_packages === 0 ? InventoryStatus::SOLD_OUT : ($lot->reserved_packages > 0 ? InventoryStatus::RESERVED : InventoryStatus::IN_STOCK);
                $lot->update(['available_packages' => $lot->available_packages, 'sold_packages' => $lot->sold_packages, 'status' => $status->value]);
                $lot->movements()->create(['actor_id' => $user->id, 'type' => StockMovementType::SOLD, 'quantity' => $quantity, 'resulting_available' => $lot->available_packages, 'resulting_reserved' => $lot->reserved_packages, 'resulting_sold' => $lot->sold_packages, 'reference_type' => 'retail_sale', 'reference_id' => $sale->id, 'occurred_at' => now()]);
                $batch = FishBatch::findOrFail($lot->fish_batch_id);
                $batch->events()->create(['organization_id' => $organization->id, 'actor_id' => $user->id, 'event_type' => 'RETAIL_SALE', 'title' => 'Verified package sold', 'public_data' => ['package_count' => $quantity, 'retailer' => $organization->name], 'occurred_at' => now()]);
                $remaining = (int) InventoryLot::query()->where('fish_batch_id', $lot->fish_batch_id)->sum('available_packages') + (int) InventoryLot::query()->where('fish_batch_id', $lot->fish_batch_id)->sum('reserved_packages');
                if ($remaining === 0) {
                    $batch->update(['status' => BatchStatus::SOLD]);
                }
            }

            return $sale->load(['location', 'items.inventoryLot.label', 'items.inventoryLot.batch.species']);
        });
    }

    private function fingerprint(array $data): string
    {
        $items = collect($data['items'])->map(fn (array $item): array => [
            'inventory_lot_id' => (string) $item['inventory_lot_id'],
            'quantity' => (int) $item['quantity'],
            'unit_price' => number_format((float) $item['unit_price'], 2, '.', ''),
        ])->sortBy('inventory_lot_id')->values()->all();

        return hash('sha256', json_encode(['retail_location_id' => (string) $data['retail_location_id'], 'items' => $items], JSON_THROW_ON_ERROR));
    }
}
