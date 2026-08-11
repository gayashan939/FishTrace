<?php

namespace App\Http\Resources\Retail;

use App\Http\Resources\Fisher\FishBatchResource;
use App\Models\InventoryLot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class InventoryLotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lot = $this->model();

        return [
            'id' => $lot->id,
            'retail_receipt_id' => $lot->retail_receipt_id,
            'package_label_id' => $lot->package_label_id,
            'fish_batch_id' => $lot->fish_batch_id,
            'organization_id' => $lot->organization_id,
            'retail_location_id' => $lot->retail_location_id,
            'status' => $lot->status,
            'total_packages' => $lot->total_packages,
            'available_packages' => $lot->available_packages,
            'reserved_packages' => $lot->reserved_packages,
            'sold_packages' => $lot->sold_packages,
            'default_unit_price' => $lot->default_unit_price,
            'low_stock_threshold_kg' => $lot->low_stock_threshold_kg,
            'expires_at' => $lot->expires_at,
            'created_at' => $lot->created_at,
            'updated_at' => $lot->updated_at,
            'receipt' => $this->when($lot->relationLoaded('receipt'), fn () => new RetailReceiptResource($lot->receipt)),
            'label' => $this->when($lot->relationLoaded('label'), fn () => new PackageLabelResource($lot->label)),
            'batch' => $this->when($lot->relationLoaded('batch'), fn () => new FishBatchResource($lot->batch)),
            'location' => $this->when($lot->relationLoaded('location'), fn () => new RetailLocationResource($lot->location)),
            'movements' => $this->when($lot->relationLoaded('movements'), fn () => StockMovementResource::collection($lot->movements)),
        ];
    }

    private function model(): InventoryLot
    {
        if (! $this->resource instanceof InventoryLot) {
            throw new LogicException('InventoryLotResource requires an InventoryLot model.');
        }

        return $this->resource;
    }
}
