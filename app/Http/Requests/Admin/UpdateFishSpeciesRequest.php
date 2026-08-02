<?php

namespace App\Http\Requests\Admin;

use App\Models\FishSpecies;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFishSpeciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('species')) ?? false;
    }

    public function rules(): array
    {
        /** @var FishSpecies $species */
        $species = $this->route('species');

        return [
            'common_name' => ['required', 'string', 'max:160', Rule::unique('fish_species', 'common_name')->ignore($species->id)],
            'scientific_name' => ['nullable', 'string', 'max:160'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['common_name' => trim((string) $this->input('common_name')), 'scientific_name' => $this->filled('scientific_name') ? trim((string) $this->input('scientific_name')) : null, 'is_active' => $this->boolean('is_active')]);
    }
}
