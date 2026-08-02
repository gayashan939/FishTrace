<?php

namespace App\Http\Resources\Retail;

use App\Models\RetailSale;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class RetailSaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sale = $this->model();

        return [
            'id' => $sale->id,
            'organization_id' => $sale->organization_id,
            'retail_location_id' => $sale->retail_location_id,
            'sold_by' => $sale->sold_by,
            'client_reference' => $sale->client_reference,
            'receipt_number' => $sale->receipt_number,
            'status' => $sale->status,
            'subtotal' => $sale->subtotal,
            'total' => $sale->total,
            'sold_at' => $sale->sold_at,
            'created_at' => $sale->created_at,
            'updated_at' => $sale->updated_at,
            'location' => $this->when($sale->relationLoaded('location'), fn () => new RetailLocationResource($sale->location)),
            'items' => $this->when($sale->relationLoaded('items'), fn () => RetailSaleItemResource::collection($sale->items)),
        ];
    }

    private function model(): RetailSale
    {
        if (! $this->resource instanceof RetailSale) {
            throw new LogicException('RetailSaleResource requires a RetailSale model.');
        }

        return $this->resource;
    }
}
