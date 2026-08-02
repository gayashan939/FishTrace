<?php

namespace App\Http\Requests\Processor;

use Illuminate\Foundation\Http\FormRequest;

class ProcessorBatchDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('PROCESSOR') ?? false;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
