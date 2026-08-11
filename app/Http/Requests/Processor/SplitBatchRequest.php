<?php

namespace App\Http\Requests\Processor;

use App\Models\FishBatch;
use Illuminate\Foundation\Http\FormRequest;

class SplitBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');

        return $batch instanceof FishBatch && ($this->user()?->can('split', $batch) ?? false);
    }

    public function rules(): array
    {
        return ['children' => ['required', 'array', 'min:1', 'max:50'], 'children.*.weight_kg' => ['required', 'numeric', 'gt:0'], 'children.*.product_type' => ['nullable', 'string', 'max:100'], 'children.*.package_count' => ['required', 'integer', 'min:1', 'max:50']];
    }
}
