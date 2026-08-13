<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransportTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('transportTrip');

        return $trip !== null && ($this->user()?->can('update', $trip) ?? false);
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['sometimes', 'uuid', 'exists:vehicles,id'],
            'driver_name' => ['sometimes', 'string', 'max:120'],
            'origin' => ['sometimes', 'string', 'max:160'],
            'origin_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90', 'required_with:origin_longitude'],
            'origin_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180', 'required_with:origin_latitude'],
            'destination' => ['sometimes', 'string', 'max:160'],
            'destination_latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90', 'required_with:destination_longitude'],
            'destination_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180', 'required_with:destination_latitude'],
            'estimated_distance_km' => ['sometimes', 'nullable', 'numeric', 'gt:0'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
