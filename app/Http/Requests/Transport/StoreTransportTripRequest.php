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
        return ['vehicle_id' => ['required', 'uuid', 'exists:vehicles,id'], 'driver_name' => ['required', 'string', 'max:120'], 'origin' => ['required', 'string', 'max:160'], 'destination' => ['required', 'string', 'max:160'], 'estimated_distance_km' => ['nullable', 'numeric', 'gt:0'], 'scheduled_at' => ['nullable', 'date']];
    }
}
