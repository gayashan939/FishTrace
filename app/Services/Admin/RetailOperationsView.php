<?php

namespace App\Services\Admin;

use App\Models\InventoryLot;
use App\Models\Organization;
use App\Models\RetailLocation;
use App\Models\RetailReceipt;
use App\Models\RetailSale;
use App\Models\User;

class RetailOperationsView
{
    public function retailer(User $retailer): array
    {
        abort_unless($retailer->hasRole('RETAILER'), 404);
        $retailer->load('organizations:id,name,code,type,is_active');
        $organization = $retailer->organizations->firstWhere('type', 'RETAILER');
        abort_unless($organization instanceof Organization, 404);
        $locations = RetailLocation::query()->where('organization_id', $organization->id)->withCount(['inventoryLots', 'sales'])->orderBy('name')->limit(100)->get();
        $receipts = RetailReceipt::query()->where('retailer_organization_id', $organization->id)->with(['label:id,label_code', 'location:id,name'])->latest('received_at')->limit(100)->get();
        $sales = RetailSale::query()->where('organization_id', $organization->id)->with('location:id,name')->latest('sold_at')->limit(100)->get();

        return compact('retailer', 'organization', 'locations', 'receipts', 'sales');
    }

    public function location(RetailLocation $location): array
    {
        $location->load(['organization:id,name,code', 'receipts' => fn ($q) => $q->with('label:id,label_code')->latest('received_at')->limit(100), 'inventoryLots' => fn ($q) => $q->with(['label:id,label_code', 'batch:id,batch_code'])->latest()->limit(100), 'sales' => fn ($q) => $q->latest('sold_at')->limit(100)])->loadCount(['receipts', 'inventoryLots', 'sales']);

        return compact('location');
    }

    public function receipt(RetailReceipt $receipt): array
    {
        $receipt->load(['label:id,fish_batch_id,label_code,package_weight_kg,package_count,printed_at', 'batch.organization:id,name,code', 'retailerOrganization:id,name,code', 'location:id,code,name,address', 'receiver:id,name,email', 'inventoryLot']);

        return compact('receipt');
    }

    public function inventory(InventoryLot $lot): array
    {
        $lot->load(['receipt.receiver:id,name', 'label:id,label_code,package_weight_kg,package_count', 'batch.organization:id,name,code', 'organization:id,name,code', 'location:id,code,name,address', 'movements' => fn ($q) => $q->with('actor:id,name')->latest('occurred_at')->limit(250), 'saleItems.sale:id,receipt_number,status,sold_at,total'])->loadCount(['movements', 'saleItems']);

        return compact('lot');
    }

    public function sale(RetailSale $sale): array
    {
        $sale->load(['organization:id,name,code', 'location:id,code,name,address', 'seller:id,name,email', 'items.inventoryLot.label:id,label_code', 'items.inventoryLot.batch:id,batch_code,product_type']);

        return compact('sale');
    }
}
