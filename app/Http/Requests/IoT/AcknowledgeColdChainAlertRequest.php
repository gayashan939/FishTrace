<?php

namespace App\Http\Requests\IoT;

use App\Models\ColdChainAlert;
use App\Models\TransportTrip;
use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeColdChainAlertRequest extends FormRequest
{
    public function authorize(): bool
    {
        $alert = $this->route('alert');
        if (! $alert instanceof ColdChainAlert) {
            return false;
        }
        $trip = TransportTrip::query()->find($alert->transport_trip_id);

        return $trip !== null && ($this->user()?->can('update', $trip) ?? false);
    }

    public function rules(): array
    {
        return ['note' => ['nullable', 'string', 'max:1000']];
    }
}
