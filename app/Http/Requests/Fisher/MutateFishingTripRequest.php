<?php

namespace App\Http\Requests\Fisher;

use Illuminate\Foundation\Http\FormRequest;

class MutateFishingTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('fishingTrip')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
