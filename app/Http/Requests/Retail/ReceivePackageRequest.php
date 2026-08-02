<?php

namespace App\Http\Requests\Retail;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('receive', $this->route('label')) ?? false;
    }

    public function rules(): array
    {
        return [
            'retail_location_id' => ['required', 'uuid', 'exists:retail_locations,id'],
            'received_package_count' => ['required', 'integer', 'min:1'],
            'condition_temperature' => ['nullable', 'numeric', 'between:-40,30'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
