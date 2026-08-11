<?php

namespace App\Http\Requests\Transport;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Vehicle::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:80', 'unique:vehicles'],
            'name' => ['required', 'string', 'max:120'],
            'capacity_tonnes' => ['required', 'numeric', 'gt:0'],
            'vehicle_type' => ['nullable', 'string', 'max:80'],
            'refrigeration_category' => ['nullable', 'string', 'max:80'],
            'reefer_unit' => ['nullable', 'string', 'max:120'],
            'min_temperature_celsius' => ['nullable', 'numeric', 'between:-40,30'],
            'max_temperature_celsius' => ['nullable', 'numeric', 'between:-40,40'],
            'default_driver_name' => ['nullable', 'string', 'max:120'],
        ];
    }
}
