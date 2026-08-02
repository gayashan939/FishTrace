<?php

namespace App\Services\Admin;

use App\Models\FishBatch;
use App\Models\InventoryLot;
use App\Models\Organization;
use App\Models\PackageLabel;
use App\Models\RetailLocation;
use App\Models\RetailSale;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RetailOperationsExport
{
    public function __construct(private RetailOperationsQuery $query, private AuditLogger $audit) {}

    public function inventory(User $actor, array $filters): StreamedResponse
    {
        $rows = $this->query->inventory($filters)->limit(10000)->get();
        $this->audit->record('RETAIL_INVENTORY_EXPORTED', null, null, ['filters' => $filters, 'row_count' => $rows->count()], $actor);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, ['label_code', 'batch_code', 'organization', 'location', 'status', 'total_packages', 'available_packages', 'reserved_packages', 'sold_packages', 'expires_at'], escape: '\\');
            foreach ($rows as $lot) {
                if (! $lot instanceof InventoryLot) {
                    continue;
                } $label = $lot->label;
                $batch = $lot->batch;
                $org = $lot->organization;
                $location = $lot->location;
                fputcsv($out, [$label instanceof PackageLabel ? $label->label_code : null, $batch instanceof FishBatch ? $batch->batch_code : null, $org instanceof Organization ? $org->name : null, $location instanceof RetailLocation ? $location->name : null, $lot->getRawOriginal('status'), $lot->total_packages, $lot->available_packages, $lot->reserved_packages, $lot->sold_packages, $lot->expires_at ? Carbon::parse($lot->expires_at)->toDateString() : null], escape: '\\');
            }
            fclose($out);
        }, 'fishtrace-retail-inventory-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function sales(User $actor, array $filters): StreamedResponse
    {
        $rows = $this->query->sales($filters)->limit(10000)->get();
        $this->audit->record('RETAIL_SALES_EXPORTED', null, null, ['filters' => $filters, 'row_count' => $rows->count()], $actor);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fputcsv($out, ['receipt_number', 'organization', 'location', 'seller', 'status', 'item_count', 'subtotal', 'total', 'sold_at'], escape: '\\');
            foreach ($rows as $sale) {
                if (! $sale instanceof RetailSale) {
                    continue;
                } $org = $sale->organization;
                $location = $sale->location;
                $seller = $sale->seller;
                fputcsv($out, [$sale->receipt_number, $org instanceof Organization ? $org->name : null, $location instanceof RetailLocation ? $location->name : null, $seller instanceof User ? $seller->name : null, $sale->getRawOriginal('status'), $sale->items_count, $sale->subtotal, $sale->total, Carbon::parse($sale->sold_at)->toIso8601String()], escape: '\\');
            }
            fclose($out);
        }, 'fishtrace-retail-sales-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }
}
