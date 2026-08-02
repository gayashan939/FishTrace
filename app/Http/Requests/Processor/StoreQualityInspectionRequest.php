<?php

namespace App\Http\Requests\Processor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQualityInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('PROCESSOR') || $this->user()?->hasRole('INSPECTOR');
    }

    public function rules(): array
    {
        return ['processing_record_id' => ['required', 'uuid', 'exists:processing_records,id'], 'result' => ['required', Rule::in(['PASSED', 'FAILED', 'CONDITIONAL'])], 'quality_grade_id' => ['required_if:result,PASSED,CONDITIONAL', 'nullable', 'uuid', 'exists:quality_grades,id'], 'product_temperature' => ['required', 'numeric', 'between:-20,20'], 'ph_level' => ['nullable', 'numeric', 'between:4,9'], 'appearance' => ['required', 'string', 'max:100'], 'odor' => ['required', 'string', 'max:100'], 'notes' => ['required_if:result,FAILED,CONDITIONAL', 'nullable', 'string', 'max:2000']];
    }
}
