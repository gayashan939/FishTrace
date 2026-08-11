<?php

namespace App\Http\Requests\Fisher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('catchRecord')) ?? false;
    }

    public function rules(): array
    {
        return [
            'fish_species_id' => ['required', 'uuid', Rule::exists('fish_species', 'id')->where('is_active', true)],
            'fishing_gear_type_id' => ['nullable', 'uuid', Rule::exists('fishing_gear_types', 'id')->where('is_active', true)],
            'weight_kg' => ['required', 'numeric', 'gt:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'condition' => ['nullable', Rule::in(['EXCELLENT', 'GOOD', 'FAIR'])],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'caught_at' => ['required', 'date'],
            'client_created_at' => ['nullable', 'date'],
        ];
    }
}
