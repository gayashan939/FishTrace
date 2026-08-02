<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrganizationType;
use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Organization::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', 'unique:organizations,code'],
            'type' => ['required', Rule::enum(OrganizationType::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => mb_strtoupper((string) $this->input('code')), 'type' => mb_strtoupper((string) $this->input('type'))]);
    }
}
