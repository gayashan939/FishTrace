<?php

namespace App\Http\Requests\IoT;

use App\Models\TransportTrip;
use Illuminate\Foundation\Http\FormRequest;

class TransportTelemetryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('transportTrip');

        return $trip instanceof TransportTrip && ($this->user()?->can('view', $trip) ?? false);
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
