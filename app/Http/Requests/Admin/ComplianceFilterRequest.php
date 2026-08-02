<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ComplianceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('ADMIN');
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'], 'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'status' => ['nullable', 'string', 'max:60'], 'result' => ['nullable', 'in:FAILED,CONDITIONAL'],
            'category' => ['nullable', 'string', 'max:60'], 'entity_type' => ['nullable', 'string', 'max:60'],
            'risk_level' => ['nullable', 'string', 'max:30'], 'read' => ['nullable', 'in:read,unread'],
            'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
