<?php

namespace App\Models;

use App\Enums\InventoryStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryLot extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['retail_receipt_id', 'package_label_id', 'fish_batch_id', 'organization_id', 'retail_location_id', 'status', 'total_packages', 'available_packages', 'reserved_packages', 'sold_packages', 'expires_at'];

    protected function casts(): array
    {
        return ['status' => InventoryStatus::class, 'expires_at' => 'datetime'];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(RetailReceipt::class, 'retail_receipt_id');
    }

    public function label(): BelongsTo
    {
        return $this->belongsTo(PackageLabel::class, 'package_label_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(FishBatch::class, 'fish_batch_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(RetailLocation::class, 'retail_location_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->orderBy('occurred_at');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(RetailSaleItem::class);
    }
}
