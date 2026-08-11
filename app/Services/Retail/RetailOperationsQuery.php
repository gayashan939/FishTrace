<?php

namespace App\Services\Retail;

use App\Enums\BatchStatus;
use App\Enums\InventoryStatus;
use App\Models\ColdChainAlert;
use App\Models\InventoryLot;
use App\Models\PackageLabel;
use App\Models\RetailReceipt;
use App\Models\RetailSale;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class RetailOperationsQuery
{
    public function dashboard(User $user): array
    {
        $query = InventoryLot::query()->where('organization_id', $user->primaryOrganization()?->id);

        return [
            'lot_count' => (clone $query)->count(),
            'available_packages' => (int) (clone $query)->whereIn('status', [InventoryStatus::IN_STOCK, InventoryStatus::RESERVED])->sum('available_packages'),
            'reserved_packages' => (int) (clone $query)->whereIn('status', [InventoryStatus::IN_STOCK, InventoryStatus::RESERVED])->sum('reserved_packages'),
            'sold_packages' => (int) (clone $query)->sum('sold_packages'),
            'recalled_lots' => (clone $query)->where('status', InventoryStatus::RECALLED)->count(),
            'expired_lots' => (clone $query)->where('status', InventoryStatus::EXPIRED)->count(),
        ];
    }

    public function incoming(int $perPage): LengthAwarePaginator
    {
        return PackageLabel::query()
            ->with(['batch.species', 'batch.organization'])
            ->whereDoesntHave('retailReceipt')
            ->whereHas('batch', fn ($query) => $query->whereIn('status', [BatchStatus::PROCESSED, BatchStatus::READY_FOR_TRANSPORT, BatchStatus::IN_TRANSPORT])->where('is_recalled', false))
            ->latest()
            ->paginate($perPage);
    }

    public function resolveIncomingLabel(string $scannedValue): ?PackageLabel
    {
        $value = trim($scannedValue);
        $path = parse_url($value, PHP_URL_PATH);
        $token = is_string($path) ? basename(trim($path, '/')) : $value;

        return PackageLabel::query()
            ->with(['batch.species', 'batch.organization'])
            ->whereDoesntHave('retailReceipt')
            ->whereHas('batch', fn ($query) => $query->whereIn('status', [BatchStatus::PROCESSED, BatchStatus::READY_FOR_TRANSPORT, BatchStatus::IN_TRANSPORT])->where('is_recalled', false))
            ->where(function ($query) use ($value, $token): void {
                if (Str::isUuid($value)) {
                    $query->orWhereKey($value);
                }
                $query->orWhere('label_code', $value)
                    ->orWhere('label_code', $token)
                    ->orWhere('public_token', $token);
            })
            ->first();
    }

    public function receipts(User $user, int $perPage): LengthAwarePaginator
    {
        return RetailReceipt::query()
            ->where('retailer_organization_id', $user->primaryOrganization()?->id)
            ->with(['label', 'batch.species', 'location', 'inventoryLot'])
            ->latest('received_at')
            ->paginate($perPage);
    }

    public function receipt(RetailReceipt $receipt): RetailReceipt
    {
        return $receipt->load(['label', 'batch.species', 'location', 'inventoryLot.movements']);
    }

    public function inventory(User $user, array $filters): LengthAwarePaginator
    {
        return InventoryLot::query()
            ->where('organization_id', $user->primaryOrganization()?->id)
            ->with(['label', 'batch.species', 'location'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['location_id'] ?? null, fn ($query, $locationId) => $query->where('retail_location_id', $locationId))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 25));
    }

    public function inventoryLot(InventoryLot $lot): InventoryLot
    {
        return $lot->load(['receipt', 'label', 'batch.species', 'location', 'movements']);
    }

    public function sales(User $user, int $perPage): LengthAwarePaginator
    {
        return RetailSale::query()
            ->where('organization_id', $user->primaryOrganization()?->id)
            ->with(['location', 'items'])
            ->latest('sold_at')
            ->paginate($perPage);
    }

    public function sale(RetailSale $sale): RetailSale
    {
        return $sale->load(['location', 'items.inventoryLot.label', 'items.inventoryLot.batch.species']);
    }

    public function alerts(User $user, array $filters): LengthAwarePaginator
    {
        $organizationId = $user->primaryOrganization()?->id;

        return ColdChainAlert::query()
            ->with('batch:id,batch_code')
            ->whereNotNull('fish_batch_id')
            ->whereHas('batch.inventoryLots', fn ($query) => $query->where('organization_id', $organizationId))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['severity'] ?? null, fn ($query, $severity) => $query->where('severity', $severity))
            ->latest('last_detected_at')
            ->paginate((int) ($filters['per_page'] ?? 20));
    }
}
