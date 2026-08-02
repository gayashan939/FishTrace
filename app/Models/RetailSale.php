<?php

namespace App\Models;

use App\Enums\RetailSaleStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetailSale extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'retail_location_id', 'sold_by', 'client_reference', 'request_fingerprint', 'receipt_number', 'status', 'subtotal', 'total', 'sold_at'];

    protected function casts(): array
    {
        return ['status' => RetailSaleStatus::class, 'subtotal' => 'decimal:2', 'total' => 'decimal:2', 'sold_at' => 'datetime'];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(RetailLocation::class, 'retail_location_id');
    }

    /** @return HasMany<RetailSaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(RetailSaleItem::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }
}
