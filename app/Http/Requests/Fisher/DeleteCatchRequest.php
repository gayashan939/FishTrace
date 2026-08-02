<?php

namespace App\Http\Requests\Fisher;

use Illuminate\Foundation\Http\FormRequest;

class DeleteCatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('catchRecord')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
