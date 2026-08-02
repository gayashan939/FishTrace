<?php

namespace App\Http\Requests\Transport;

use Illuminate\Foundation\Http\FormRequest;

class DeleteVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->route('vehicle')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
