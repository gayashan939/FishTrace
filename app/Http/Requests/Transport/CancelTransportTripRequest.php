<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class CancelTransportTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('transportTrip');

        return $trip !== null && ($this->user()?->can('update', $trip) ?? false);
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:10', 'max:1000']];
    }
}
