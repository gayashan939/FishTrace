<?php

namespace App\Http\Resources\Retail;

use App\Models\RetailSaleItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class RetailSaleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $item = $this->model();

        return [
            'id' => $item->id,
            'retail_sale_id' => $item->retail_sale_id,
            'inventory_lot_id' => $item->inventory_lot_id,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'line_total' => $item->line_total,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'inventory_lot' => $this->when($item->relationLoaded('inventoryLot'), fn () => new InventoryLotResource($item->inventoryLot)),
        ];
    }

    private function model(): RetailSaleItem
    {
        if (! $this->resource instanceof RetailSaleItem) {
            throw new LogicException('RetailSaleItemResource requires a RetailSaleItem model.');
        }

        return $this->resource;
    }
}
