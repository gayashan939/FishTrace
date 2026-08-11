<?php

namespace App\Http\Requests\Processor;

use Illuminate\Foundation\Http\FormRequest;

class StoreProcessingRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('PROCESSOR') ?? false;
    }

    public function rules(): array
    {
        return ['fish_batch_id' => ['required', 'uuid', 'exists:fish_batches,id'], 'processing_type_id' => ['nullable', 'uuid', 'exists:processing_types,id'], 'operator_name' => ['nullable', 'string', 'max:120'], 'processing_area' => ['nullable', 'string', 'max:120'], 'input_weight_kg' => ['required', 'numeric', 'gt:0'], 'notes' => ['nullable', 'string', 'max:2000']];
    }
}
