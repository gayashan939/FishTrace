<?php

namespace App\Http\Requests\IoT;

use App\Models\TransportTrip;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransportAlertFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('transportTrip');

        return $trip instanceof TransportTrip && ($this->user()?->can('view', $trip) ?? false);
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['OPEN', 'ACKNOWLEDGED', 'RESOLVED'])],
            'severity' => ['nullable', Rule::in(['INFO', 'WARNING', 'CRITICAL'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
