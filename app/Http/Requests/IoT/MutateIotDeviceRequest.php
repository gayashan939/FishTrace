<?php

namespace App\Http\Requests\IoT;

use Illuminate\Foundation\Http\FormRequest;

class MutateIotDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('device')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
