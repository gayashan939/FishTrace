<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('managedUser')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('managedUser'))],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['required', 'integer', 'distinct', 'exists:roles,id'],
            'organization_ids' => ['required', 'array', 'min:1'],
            'organization_ids.*' => ['required', 'uuid', 'distinct', 'exists:organizations,id'],
            'primary_organization_id' => ['required', 'uuid', Rule::in($this->input('organization_ids', []))],
        ];
    }
}
