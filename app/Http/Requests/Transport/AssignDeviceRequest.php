<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class AssignDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('transportTrip');

        return $trip !== null && ($this->user()?->can('update', $trip) ?? false);
    }

    public function rules(): array
    {
        return ['iot_device_id' => ['required', 'uuid', 'exists:iot_devices,id'], 'expires_at' => ['nullable', 'date', 'after:now']];
    }
}
