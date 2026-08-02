<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FishingFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('ADMIN');
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
            'species_id' => ['nullable', 'uuid', 'exists:fish_species,id'],
            'fisher_id' => ['nullable', 'uuid', 'exists:users,id'],
            'boat_id' => ['nullable', 'uuid', 'exists:boats,id'],
            'allocation' => ['nullable', 'in:UNALLOCATED,PARTIAL,FULL,OVERALLOCATED,MISMATCH'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'in:name,email,registration_number,capacity_kg,trip_code,status,departed_at,caught_at,weight_kg,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
