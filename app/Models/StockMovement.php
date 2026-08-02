<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasUuids;

    protected $fillable = ['inventory_lot_id', 'actor_id', 'type', 'quantity', 'resulting_available', 'resulting_reserved', 'resulting_sold', 'reference_type', 'reference_id', 'reason', 'occurred_at'];

    protected function casts(): array
    {
        return ['type' => StockMovementType::class, 'occurred_at' => 'datetime'];
    }

    public function inventoryLot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
