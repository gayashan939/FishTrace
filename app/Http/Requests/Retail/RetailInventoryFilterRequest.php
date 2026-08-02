<?php

namespace App\Http\Requests\Retail;

use App\Enums\InventoryStatus;
use App\Models\InventoryLot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RetailInventoryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', InventoryLot::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(InventoryStatus::class)],
            'location_id' => ['nullable', 'uuid'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('status')) {
            $this->merge(['status' => strtoupper($this->string('status')->toString())]);
        }
    }
}
