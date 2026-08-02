<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UserDirectoryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:ACTIVE,DISABLED'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'locked' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:name,email,status,last_login_at,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
