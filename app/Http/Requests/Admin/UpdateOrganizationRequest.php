<?php

namespace App\Http\Requests\Admin;

use App\Enums\OrganizationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('organization')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('organizations', 'code')->ignore($this->route('organization'))],
            'type' => ['required', Rule::enum(OrganizationType::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => mb_strtoupper((string) $this->input('code')), 'type' => mb_strtoupper((string) $this->input('type'))]);
    }
}
