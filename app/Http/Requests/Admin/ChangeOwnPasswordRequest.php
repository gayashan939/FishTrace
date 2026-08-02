<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangeOwnPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('ADMIN') ?? false;
    }

    public function rules(): array
    {
        return ['current_password' => ['required', 'current_password:web'], 'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()]];
    }
}
