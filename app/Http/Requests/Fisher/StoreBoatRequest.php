<?php

namespace App\Http\Requests\Fisher;

use App\Models\Boat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBoatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Boat::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:50', Rule::unique('boats', 'registration_number')],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(['LONG_LINER', 'GILLNETTER', 'DAY_BOAT'])],
            'capacity_kg' => ['nullable', 'numeric', 'gt:0'],
            'length_meters' => ['required', 'numeric', 'gt:0', 'max:999999.99'],
            'engine_details' => ['required', 'string', 'max:160'],
            'home_port' => ['required', 'string', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
