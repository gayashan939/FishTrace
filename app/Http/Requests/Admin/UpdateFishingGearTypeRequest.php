<?php

namespace App\Http\Requests\Admin;

use App\Models\FishingGearType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFishingGearTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('gear')) ?? false;
    }

    public function rules(): array
    {
        /** @var FishingGearType $gear */
        $gear = $this->route('gear');

        return ['name' => ['required', 'string', 'max:160', Rule::unique('fishing_gear_types', 'name')->ignore($gear->id)], 'is_active' => ['required', 'boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name')), 'is_active' => $this->boolean('is_active')]);
    }
}
