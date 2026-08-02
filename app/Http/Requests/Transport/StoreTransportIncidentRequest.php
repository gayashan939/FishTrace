<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransportIncidentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('transportTrip');

        return $trip !== null && ($this->user()?->can('update', $trip) ?? false);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:80'],
            'severity' => ['required', Rule::in(['INFO', 'WARNING', 'CRITICAL'])],
            'description' => ['required', 'string', 'max:2000'],
            'occurred_at' => ['required', 'date', 'before_or_equal:now'],
        ];
    }
}
