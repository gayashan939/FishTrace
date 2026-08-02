<?php

namespace App\Http\Resources\Retail;

use App\Http\Resources\Fisher\FishBatchResource;
use App\Models\RetailReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class RetailReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $receipt = $this->model();

        return [
            'id' => $receipt->id,
            'package_label_id' => $receipt->package_label_id,
            'fish_batch_id' => $receipt->fish_batch_id,
            'retailer_organization_id' => $receipt->retailer_organization_id,
            'retail_location_id' => $receipt->retail_location_id,
            'received_by' => $receipt->received_by,
            'received_package_count' => $receipt->received_package_count,
            'received_weight_kg' => $receipt->received_weight_kg,
            'condition_temperature' => $receipt->condition_temperature,
            'notes' => $receipt->notes,
            'received_at' => $receipt->received_at,
            'created_at' => $receipt->created_at,
            'updated_at' => $receipt->updated_at,
            'label' => $this->when($receipt->relationLoaded('label'), fn () => new PackageLabelResource($receipt->label)),
            'batch' => $this->when($receipt->relationLoaded('batch'), fn () => new FishBatchResource($receipt->batch)),
            'location' => $this->when($receipt->relationLoaded('location'), fn () => new RetailLocationResource($receipt->location)),
            'inventory_lot' => $this->when($receipt->relationLoaded('inventoryLot'), fn () => $receipt->inventoryLot === null ? null : new InventoryLotResource($receipt->inventoryLot)),
        ];
    }

    private function model(): RetailReceipt
    {
        if (! $this->resource instanceof RetailReceipt) {
            throw new LogicException('RetailReceiptResource requires a RetailReceipt model.');
        }

        return $this->resource;
    }
}
