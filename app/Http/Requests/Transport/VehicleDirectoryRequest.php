<?php

namespace App\Http\Requests\Transport;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;

class VehicleDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Vehicle::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
