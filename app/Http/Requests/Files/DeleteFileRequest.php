<?php

namespace App\Http\Requests\Files;

use Illuminate\Foundation\Http\FormRequest;

class DeleteFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('file')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
