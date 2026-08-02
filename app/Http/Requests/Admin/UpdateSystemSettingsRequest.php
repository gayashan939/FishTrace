<?php

namespace App\Http\Requests\Admin;

use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', SystemSetting::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'platform_name' => ['required', 'string', 'max:80'],
            'support_email' => ['nullable', 'email:rfc', 'max:160'],
            'consumer_portal_enabled' => ['required', 'boolean'],
            'consumer_portal_notice' => ['nullable', 'string', 'max:500'],
            'telemetry_retention_hours' => ['required', 'integer', 'between:24,72'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'platform_name' => trim((string) $this->input('platform_name')),
            'support_email' => $this->filled('support_email') ? mb_strtolower(trim((string) $this->input('support_email'))) : '',
            'consumer_portal_enabled' => $this->boolean('consumer_portal_enabled'),
            'consumer_portal_notice' => $this->filled('consumer_portal_notice') ? trim((string) $this->input('consumer_portal_notice')) : '',
        ]);
    }
}
