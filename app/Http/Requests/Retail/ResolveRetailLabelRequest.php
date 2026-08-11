<?php

namespace App\Http\Requests\Retail;

use Illuminate\Foundation\Http\FormRequest;

class ResolveRetailLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('RETAILER') ?? false;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:2048']];
    }
}
