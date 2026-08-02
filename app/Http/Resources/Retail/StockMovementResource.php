<?php

namespace App\Http\Resources\Retail;

use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $movement = $this->model();

        return [
            'id' => $movement->id,
            'inventory_lot_id' => $movement->inventory_lot_id,
            'actor_id' => $movement->actor_id,
            'type' => $movement->type,
            'quantity' => $movement->quantity,
            'resulting_available' => $movement->resulting_available,
            'resulting_reserved' => $movement->resulting_reserved,
            'resulting_sold' => $movement->resulting_sold,
            'reference_type' => $movement->reference_type,
            'reference_id' => $movement->reference_id,
            'reason' => $movement->reason,
            'occurred_at' => $movement->occurred_at,
            'created_at' => $movement->created_at,
            'updated_at' => $movement->updated_at,
        ];
    }

    private function model(): StockMovement
    {
        if (! $this->resource instanceof StockMovement) {
            throw new LogicException('StockMovementResource requires a StockMovement model.');
        }

        return $this->resource;
    }
}
