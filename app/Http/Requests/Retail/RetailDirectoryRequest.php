<?php

namespace App\Http\Requests\Retail;

use Illuminate\Foundation\Http\FormRequest;

class RetailDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('RETAILER') ?? false;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
