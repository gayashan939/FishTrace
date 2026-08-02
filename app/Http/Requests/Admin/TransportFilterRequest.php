<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class TransportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('ADMIN');
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'], 'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'status' => ['nullable', 'string', 'max:50'], 'severity' => ['nullable', 'in:INFO,WARNING,CRITICAL'],
            'type' => ['nullable', 'string', 'max:80'], 'device_id' => ['nullable', 'uuid', 'exists:iot_devices,id'],
            'trip_id' => ['nullable', 'uuid', 'exists:transport_trips,id'], 'vehicle_id' => ['nullable', 'uuid', 'exists:vehicles,id'],
            'is_active' => ['nullable', 'boolean'], 'sync_status' => ['nullable', 'in:PENDING,SYNCED,FAILED'],
            'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'in:name,email,registration_number,trip_code,status,device_code,last_seen_at,recorded_at,severity,last_detected_at,created_at'],
            'direction' => ['nullable', 'in:asc,desc'], 'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
