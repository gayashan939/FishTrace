<?php

namespace App\Http\Requests\Retail;

use App\Models\RetailSale;
use Illuminate\Foundation\Http\FormRequest;

class StoreRetailSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', RetailSale::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'retail_location_id' => ['required', 'uuid', 'exists:retail_locations,id'],
            'client_reference' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.inventory_lot_id' => ['required', 'uuid', 'distinct', 'exists:inventory_lots,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
        ];
    }
}
