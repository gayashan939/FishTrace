<?php

namespace App\Http\Requests\IoT;

use App\Models\TransportTrip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SensorSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('transportTrip');

        return $trip instanceof TransportTrip && ($this->user()?->can('view', $trip) ?? false);
    }

    public function rules(): array
    {
        return ['period' => ['nullable', Rule::in(['hour', 'day'])]];
    }
}
