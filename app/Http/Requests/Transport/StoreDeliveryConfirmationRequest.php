<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryConfirmationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $trip = $this->route('transportTrip');

        return $trip !== null && ($this->user()?->can('update', $trip) ?? false);
    }

    public function rules(): array
    {
        return [
            'receiver_name' => ['required', 'string', 'max:120'],
            'receiver_contact' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'delivered_at' => ['required', 'date', 'before_or_equal:now'],
        ];
    }
}
