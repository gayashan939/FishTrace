<?php

namespace App\Http\Requests\IoT;

use App\Models\IotDevice;
use Illuminate\Foundation\Http\FormRequest;

class IotDeviceDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', IotDevice::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
