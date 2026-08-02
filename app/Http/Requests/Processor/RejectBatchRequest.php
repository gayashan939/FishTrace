<?php

namespace App\Http\Requests\Processor;

use App\Models\FishBatch;
use Illuminate\Foundation\Http\FormRequest;

class RejectBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');

        return $batch instanceof FishBatch && ($this->user()?->can('receive', $batch) ?? false);
    }

    public function rules(): array
    {
        return ['rejection_reason' => ['required', 'string', 'min:5', 'max:1000'], 'received_weight_kg' => ['nullable', 'numeric', 'gt:0'], 'notes' => ['nullable', 'string', 'max:2000']];
    }
}
