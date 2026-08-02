<?php

namespace App\Http\Requests\Fisher;

use App\Enums\FileCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreBatchDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attachDocument', $this->route('batch')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('category')) {
            $this->merge(['category' => FileCategory::BATCH_DOCUMENT->value]);
        }
    }

    public function rules(): array
    {
        return [
            'category' => ['required', Rule::in([FileCategory::BATCH_DOCUMENT->value, FileCategory::CERTIFICATE->value])],
            'file' => ['required', File::types(['pdf', 'jpg', 'jpeg', 'png'])->min(1)->max(10 * 1024)],
        ];
    }
}
