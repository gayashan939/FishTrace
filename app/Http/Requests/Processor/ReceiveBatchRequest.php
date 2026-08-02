<?php

namespace App\Http\Requests\Processor;

use App\Models\FishBatch;
use Illuminate\Foundation\Http\FormRequest;

class ReceiveBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');

        return $batch instanceof FishBatch && ($this->user()?->can('receive', $batch) ?? false);
    }

    public function rules(): array
    {
        return ['received_weight_kg' => ['required', 'numeric', 'gt:0'], 'notes' => ['nullable', 'string', 'max:2000']];
    }
}
