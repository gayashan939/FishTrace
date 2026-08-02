<?php

namespace App\Http\Requests\Transport;

use App\Models\TransportTrip;
use Illuminate\Foundation\Http\FormRequest;

class TransportTripDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', TransportTrip::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
