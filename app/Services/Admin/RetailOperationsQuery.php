<?php

namespace App\Services\Admin;

use App\Models\InventoryLot;
use App\Models\RetailLocation;
use App\Models\RetailReceipt;
use App\Models\RetailSale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RetailOperationsQuery
{
    public function retailers(array $f): Builder
    {
        return User::query()->whereHas('roles', fn (Builder $q): Builder => $q->where('name', 'RETAILER'))->with('organizations:id,name,code,type')->withCount(['retailReceipts', 'retailSales'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->whereHas('organizations', fn (Builder $o): Builder => $o->where('organizations.id', $id)))->when($f['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))->orderBy('name');
    }

    public function locations(array $f): Builder
    {
        return RetailLocation::query()->with('organization:id,name,code')->withCount(['receipts', 'inventoryLots', 'sales'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%")->orWhere('address', 'like', "%{$term}%")))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organization_id', $id))->when(array_key_exists('status', $f), fn (Builder $q): Builder => in_array($f['status'], ['ACTIVE', 'INACTIVE'], true) ? $q->where('is_active', $f['status'] === 'ACTIVE') : $q)->orderBy('name');
    }

    public function receipts(array $f): Builder
    {
        return RetailReceipt::query()->with(['label:id,fish_batch_id,label_code,package_weight_kg,package_count', 'batch:id,batch_code,status,product_type', 'retailerOrganization:id,name,code', 'location:id,code,name', 'receiver:id,name', 'inventoryLot:id,retail_receipt_id,status'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->whereHas('label', fn (Builder $l): Builder => $l->where('label_code', 'like', "%{$term}%"))->orWhereHas('batch', fn (Builder $b): Builder => $b->where('batch_code', 'like', "%{$term}%"))))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('retailer_organization_id', $id))->when($f['location_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('retail_location_id', $id))->when($f['date_from'] ?? null, fn (Builder $q, string $date): Builder => $q->where('received_at', '>=', $date.' 00:00:00'))->when($f['date_to'] ?? null, fn (Builder $q, string $date): Builder => $q->where('received_at', '<=', $date.' 23:59:59'))->latest('received_at');
    }

    public function inventory(array $f): Builder
    {
        return InventoryLot::query()->with(['label:id,label_code', 'batch:id,batch_code,status,product_type,is_recalled', 'organization:id,name,code', 'location:id,code,name'])->withCount(['movements', 'saleItems'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where(fn (Builder $q): Builder => $q->whereHas('label', fn (Builder $l): Builder => $l->where('label_code', 'like', "%{$term}%"))->orWhereHas('batch', fn (Builder $b): Builder => $b->where('batch_code', 'like', "%{$term}%"))))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organization_id', $id))->when($f['location_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('retail_location_id', $id))->when($f['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))->when($f['risk'] ?? null, function (Builder $q, string $risk): Builder {
            return match ($risk) {
                'EXPIRED' => $q->where('expires_at', '<=', now()), 'EXPIRING' => $q->whereBetween('expires_at', [now(), now()->addDays(7)]), 'RECALLED' => $q->where('status', 'RECALLED'), default => $q
            };
        })->orderByRaw('CASE WHEN expires_at IS NULL THEN 1 ELSE 0 END')->orderBy('expires_at');
    }

    public function movements(array $f): Builder
    {
        return StockMovement::query()->with(['actor:id,name', 'inventoryLot:id,organization_id,retail_location_id,package_label_id,fish_batch_id,status', 'inventoryLot.organization:id,name,code', 'inventoryLot.location:id,code,name', 'inventoryLot.label:id,label_code', 'inventoryLot.batch:id,batch_code'])->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->whereHas('inventoryLot', fn (Builder $lot): Builder => $lot->whereHas('label', fn (Builder $l): Builder => $l->where('label_code', 'like', "%{$term}%"))->orWhereHas('batch', fn (Builder $b): Builder => $b->where('batch_code', 'like', "%{$term}%"))))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->whereHas('inventoryLot', fn (Builder $lot): Builder => $lot->where('organization_id', $id)))->when($f['location_id'] ?? null, fn (Builder $q, string $id): Builder => $q->whereHas('inventoryLot', fn (Builder $lot): Builder => $lot->where('retail_location_id', $id)))->when($f['type'] ?? null, fn (Builder $q, string $type): Builder => $q->where('type', $type))->when($f['date_from'] ?? null, fn (Builder $q, string $date): Builder => $q->where('occurred_at', '>=', $date.' 00:00:00'))->when($f['date_to'] ?? null, fn (Builder $q, string $date): Builder => $q->where('occurred_at', '<=', $date.' 23:59:59'))->latest('occurred_at');
    }

    public function sales(array $f): Builder
    {
        return RetailSale::query()->with(['organization:id,name,code', 'location:id,code,name', 'seller:id,name'])->withCount('items')->when($f['q'] ?? null, fn (Builder $q, string $term): Builder => $q->where('receipt_number', 'like', "%{$term}%"))->when($f['organization_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('organization_id', $id))->when($f['location_id'] ?? null, fn (Builder $q, string $id): Builder => $q->where('retail_location_id', $id))->when($f['status'] ?? null, fn (Builder $q, string $status): Builder => $q->where('status', $status))->when($f['date_from'] ?? null, fn (Builder $q, string $date): Builder => $q->where('sold_at', '>=', $date.' 00:00:00'))->when($f['date_to'] ?? null, fn (Builder $q, string $date): Builder => $q->where('sold_at', '<=', $date.' 23:59:59'))->latest('sold_at');
    }

    public function risks(array $f): Builder
    {
        return $this->inventory($f)->where(function (Builder $q): void {
            $q->where('status', 'RECALLED')->orWhere('status', 'EXPIRED')->orWhere(fn (Builder $e): Builder => $e->whereNotNull('expires_at')->where('expires_at', '<=', now()->addDays(7)));
        });
    }
}
