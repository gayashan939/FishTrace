<?php

namespace App\Http\Requests\Retail;

use App\Models\RetailSale;
use Illuminate\Foundation\Http\FormRequest;

class RetailSaleDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', RetailSale::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
