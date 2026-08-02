<?php

namespace App\Http\Requests\AI;

use App\Models\FishBatch;
use Illuminate\Foundation\Http\FormRequest;

class AIPredictionDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $batch = $this->route('batch');

        return $batch instanceof FishBatch && ($this->user()?->can('view', $batch) ?? false);
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
