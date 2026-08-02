<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RetailLocation extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'code', 'name', 'address', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function inventoryLots(): HasMany
    {
        return $this->hasMany(InventoryLot::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(RetailReceipt::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(RetailSale::class);
    }
}
