<?php

namespace App\Http\Requests\Reports;

use App\Models\ReportExport;
use Illuminate\Foundation\Http\FormRequest;

class ReportExportDirectoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ReportExport::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
