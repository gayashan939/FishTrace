<?php

namespace App\Http\Requests\IoT;

use App\Models\IotDevice;
use Illuminate\Foundation\Http\FormRequest;

class StoreIotDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', IotDevice::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'uuid', 'exists:organizations,id'],
            'device_code' => ['required', 'string', 'max:50', 'unique:iot_devices'],
            'serial_number' => ['required', 'string', 'max:100', 'unique:iot_devices'],
            'display_name' => ['required', 'string', 'max:120'],
            'firmware_version' => ['nullable', 'string', 'max:50'],
            'supports_product_temperature' => ['sometimes', 'boolean'],
            'supports_air_temperature' => ['sometimes', 'boolean'],
            'supports_humidity' => ['sometimes', 'boolean'],
            'supports_gps' => ['sometimes', 'boolean'],
            'supports_door_sensor' => ['sometimes', 'boolean'],
        ];
    }
}
