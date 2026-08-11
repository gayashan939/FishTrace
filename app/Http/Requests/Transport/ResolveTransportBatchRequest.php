<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class ResolveTransportBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('TRANSPORTER') ?? false;
    }

    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:2048']];
    }
}
