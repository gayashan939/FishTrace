<?php

namespace App\Http\Requests\Retail;

use App\Models\RetailReceipt;
use Illuminate\Foundation\Http\FormRequest;

class StoreRetailReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', RetailReceipt::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'package_label_id' => ['required', 'uuid', 'exists:package_labels,id'],
            'retail_location_id' => ['required', 'uuid', 'exists:retail_locations,id'],
            'received_package_count' => ['required', 'integer', 'min:1'],
            'condition_temperature' => ['nullable', 'numeric', 'between:-40,30'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
