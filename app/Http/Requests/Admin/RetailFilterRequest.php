<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RetailFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('ADMIN');
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'], 'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'location_id' => ['nullable', 'uuid', 'exists:retail_locations,id'], 'status' => ['nullable', 'string', 'max:50'],
            'type' => ['nullable', 'string', 'max:50'], 'risk' => ['nullable', 'in:EXPIRED,EXPIRING,RECALLED'],
            'date_from' => ['nullable', 'date_format:Y-m-d'], 'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
