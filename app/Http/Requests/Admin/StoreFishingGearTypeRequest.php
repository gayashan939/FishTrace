<?php

namespace App\Http\Requests\Admin;

use App\Models\FishingGearType;
use Illuminate\Foundation\Http\FormRequest;

class StoreFishingGearTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FishingGearType::class) ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:160', 'unique:fishing_gear_types,name'], 'is_active' => ['required', 'boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name')), 'is_active' => $this->boolean('is_active')]);
    }
}
