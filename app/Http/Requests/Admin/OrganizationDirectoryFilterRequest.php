<?php

namespace App\Http\Requests\Admin;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;

class OrganizationDirectoryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Organization::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:160'],
            'type' => ['nullable', 'in:REGULATOR,FISHER,PROCESSOR,TRANSPORTER,RETAILER,INSPECTOR'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:name,code,type,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
