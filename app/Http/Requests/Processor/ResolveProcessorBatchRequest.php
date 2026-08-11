<?php

namespace App\Http\Requests\Processor;

use Illuminate\Foundation\Http\FormRequest;

class ResolveProcessorBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('PROCESSOR') ?? false;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:2048']];
    }
}
