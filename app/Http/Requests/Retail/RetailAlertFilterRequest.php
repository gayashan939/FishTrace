<?php

namespace App\Http\Requests\Retail;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RetailAlertFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->hasRole('RETAILER') && $user->primaryOrganization() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::in(['OPEN', 'ACKNOWLEDGED', 'RESOLVED'])],
            'severity' => ['nullable', Rule::in(['INFO', 'WARNING', 'CRITICAL'])],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
