<?php

namespace App\Http\Requests\Audit;

use App\Models\AuditLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AuditLogFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $organizationId = $this->input('organization_id');

        return ($user?->can('viewAny', AuditLog::class) ?? false)
            && ($organizationId === null || $user->hasRole('ADMIN') || $organizationId === $user->primaryOrganization()?->id);
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:date_to'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'organization_id' => ['nullable', 'uuid', 'exists:organizations,id'],
            'user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'action' => ['nullable', 'string', 'max:100'],
            'entity_type' => ['nullable', Rule::in(['user', 'boat', 'fishing_trip', 'catch_record', 'fish_batch', 'batch_intake', 'processing_record', 'processing_step', 'quality_inspection', 'transport_trip', 'device_assignment', 'iot_device', 'cold_chain_alert', 'retail_receipt', 'inventory_lot', 'retail_sale', 'file_asset', 'report_export'])],
            'entity_id' => ['nullable', 'uuid'],
            'request_id' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }
}
