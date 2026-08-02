<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetManagedUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('managedUser')) ?? false;
    }

    public function rules(): array
    {
        return ['password' => ['required', 'confirmed', Password::min(10)->letters()->mixedCase()->numbers()]];
    }
}
