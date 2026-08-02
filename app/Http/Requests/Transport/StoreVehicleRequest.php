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
        ];
    }
}
