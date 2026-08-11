<?php

namespace App\Http\Requests\Fisher;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FisherDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'string', 'max:50'],
            'sort' => ['nullable', Rule::in(['created_at', 'updated_at', 'name', 'registration_number', 'departed_at', 'caught_at', 'batch_code'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }
}
