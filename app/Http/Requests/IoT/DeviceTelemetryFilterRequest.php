<?php

namespace App\Http\Requests\IoT;

use App\Models\IotDevice;
use Illuminate\Foundation\Http\FormRequest;

class DeviceTelemetryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $device = $this->route('device');

        return $device instanceof IotDevice && ($this->user()?->can('view', $device) ?? false);
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
