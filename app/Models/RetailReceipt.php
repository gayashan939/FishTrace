<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RetailReceipt extends Model
{
    use HasUuids;

    protected $fillable = ['package_label_id', 'fish_batch_id', 'retailer_organization_id', 'retail_location_id', 'received_by', 'received_package_count', 'received_weight_kg', 'condition_temperature', 'notes', 'received_at'];

    protected function casts(): array
    {
        return ['received_weight_kg' => 'decimal:3', 'condition_temperature' => 'decimal:3', 'received_at' => 'datetime'];
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

    public function inventoryLot(): HasOne
    {
        return $this->hasOne(InventoryLot::class);
    }

    public function retailerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'retailer_organization_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
