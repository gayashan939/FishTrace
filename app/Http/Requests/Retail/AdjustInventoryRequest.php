<?php

namespace App\Http\Requests\Retail;

use Illuminate\Foundation\Http\FormRequest;

class AdjustInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('lot')) ?? false;
    }

    public function rules(): array
    {
        return ['quantity' => ['required', 'integer', 'min:1']];
    }
}
