<?php

namespace App\Http\Requests\Admin;

use App\Models\FishBatch;
use Illuminate\Foundation\Http\FormRequest;

class BatchFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('ADMIN') && $this->user()->can('viewAny', FishBatch::class);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:CREATED,AVAILABLE_FOR_PROCESSING,ACCEPTED_BY_PROCESSOR,PROCESSING,PROCESSED,READY_FOR_TRANSPORT,IN_TRANSPORT,RECEIVED_BY_RETAILER,AVAILABLE_FOR_SALE,SOLD,RECALLED'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'species_id' => ['nullable', 'uuid', 'exists:fish_species,id'],
            'type' => ['nullable', 'string', 'max:50'],
            'is_recalled' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'in:batch_code,status,total_weight_kg,created_at,created_from_catch_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
