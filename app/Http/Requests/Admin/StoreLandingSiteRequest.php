<?php

namespace App\Http\Requests\Admin;

use App\Models\LandingSite;
use Illuminate\Foundation\Http\FormRequest;

class StoreLandingSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', LandingSite::class) ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:160'], 'district' => ['nullable', 'string', 'max:120'], 'is_active' => ['required', 'boolean']];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name')), 'district' => $this->filled('district') ? trim((string) $this->input('district')) : null, 'is_active' => $this->boolean('is_active')]);
    }
}
