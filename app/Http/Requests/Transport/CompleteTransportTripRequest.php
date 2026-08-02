<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class CompleteTransportTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('transportTrip')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
