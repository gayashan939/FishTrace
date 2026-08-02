<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class MutateTransportTripRequest extends FormRequest
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
