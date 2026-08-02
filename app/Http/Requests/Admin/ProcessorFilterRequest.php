<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ProcessorFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('ADMIN');
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'], 'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'status' => ['nullable', 'string', 'max:50'], 'result' => ['nullable', 'in:PASSED,FAILED,CONDITIONAL'],
            'processing_type_id' => ['nullable', 'uuid', 'exists:processing_types,id'], 'quality_grade_id' => ['nullable', 'uuid', 'exists:quality_grades,id'],
            'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
