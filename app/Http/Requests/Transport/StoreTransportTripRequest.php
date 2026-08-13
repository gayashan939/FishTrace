<?php

namespace App\Http\Requests\Transport;

use App\Models\TransportTrip;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransportTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', TransportTrip::class) ?? false;
    }

    public function rules(): array
    {
        return ['vehicle_id' => ['required', 'uuid', 'exists:vehicles,id'], 'driver_name' => ['required', 'string', 'max:120'], 'origin' => ['required', 'string', 'max:160'], 'origin_latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:origin_longitude'], 'origin_longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:origin_latitude'], 'destination' => ['required', 'string', 'max:160'], 'destination_latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:destination_longitude'], 'destination_longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:destination_latitude'], 'estimated_distance_km' => ['nullable', 'numeric', 'gt:0'], 'scheduled_at' => ['nullable', 'date']];
    }
}
