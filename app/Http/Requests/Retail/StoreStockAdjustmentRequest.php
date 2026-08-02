<?php

namespace App\Http\Requests\Retail;

use App\Models\InventoryLot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', InventoryLot::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'inventory_lot_id' => ['required', 'uuid', 'exists:inventory_lots,id'],
            'direction' => ['required', Rule::in(['ADD', 'REMOVE'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }
}
