<?php

namespace App\Http\Requests\IoT;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIotDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $device = $this->route('device');

        return $device !== null && ($this->user()?->can('update', $device) ?? false);
    }

    public function rules(): array
    {
        $device = $this->route('device');

        return [
            'device_code' => ['sometimes', 'string', 'max:50', Rule::unique('iot_devices')->ignore($device)],
            'serial_number' => ['sometimes', 'string', 'max:100', Rule::unique('iot_devices')->ignore($device)],
            'display_name' => ['sometimes', 'string', 'max:120'],
            'firmware_version' => ['nullable', 'string', 'max:50'],
            'supports_product_temperature' => ['sometimes', 'boolean'],
            'supports_air_temperature' => ['sometimes', 'boolean'],
            'supports_humidity' => ['sometimes', 'boolean'],
            'supports_gps' => ['sometimes', 'boolean'],
            'supports_door_sensor' => ['sometimes', 'boolean'],
        ];
    }
}
