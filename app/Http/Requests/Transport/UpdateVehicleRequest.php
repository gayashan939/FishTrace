<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vehicle = $this->route('vehicle');

        return $vehicle !== null && ($this->user()?->can('update', $vehicle) ?? false);
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['sometimes', 'string', 'max:80', Rule::unique('vehicles')->ignore($this->route('vehicle'))],
            'name' => ['sometimes', 'string', 'max:120'],
            'capacity_tonnes' => ['sometimes', 'numeric', 'gt:0'],
            'vehicle_type' => ['sometimes', 'nullable', 'string', 'max:80'],
            'refrigeration_category' => ['sometimes', 'nullable', 'string', 'max:80'],
            'reefer_unit' => ['sometimes', 'nullable', 'string', 'max:120'],
            'min_temperature_celsius' => ['sometimes', 'nullable', 'numeric', 'between:-40,30'],
            'max_temperature_celsius' => ['sometimes', 'nullable', 'numeric', 'between:-40,40'],
            'default_driver_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
