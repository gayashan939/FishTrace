<?php

namespace App\Http\Requests\Reports;

use App\Enums\ReportType;
use App\Models\ReportExport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ReportExport::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', Rule::enum(ReportType::class)],
            'date_from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'species_id' => ['nullable', 'uuid', 'exists:fish_species,id'],
            'status' => ['nullable', 'string', 'max:50'],
        ];
    }
}
