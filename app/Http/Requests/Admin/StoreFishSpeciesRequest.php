<?php

namespace App\Http\Requests\Admin;

use App\Models\FishSpecies;
use Illuminate\Foundation\Http\FormRequest;

class StoreFishSpeciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FishSpecies::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'common_name' => ['required', 'string', 'max:160', 'unique:fish_species,common_name'],
            'scientific_name' => ['nullable', 'string', 'max:160'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['common_name' => trim((string) $this->input('common_name')), 'scientific_name' => $this->filled('scientific_name') ? trim((string) $this->input('scientific_name')) : null, 'is_active' => $this->boolean('is_active')]);
    }
}
