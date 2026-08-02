<?php

namespace App\Http\Requests\Reports;

use App\Models\ReportExport;
use Illuminate\Foundation\Http\FormRequest;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $organizationId = $this->input('organization_id');

        return ($this->user()?->can('viewAny', ReportExport::class) ?? false)
            && ($organizationId === null || $organizationId === $this->user()->primaryOrganization()?->id);
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'species_id' => ['nullable', 'uuid', 'exists:fish_species,id'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
