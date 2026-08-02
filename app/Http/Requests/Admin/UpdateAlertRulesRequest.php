<?php

namespace App\Http\Requests\Admin;

use App\Models\AlertRuleConfig;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAlertRulesRequest extends FormRequest
{
    private const TYPES = ['HIGH_TEMPERATURE', 'LOW_TEMPERATURE', 'LOW_BATTERY', 'DEVICE_OFFLINE', 'GPS_UNAVAILABLE', 'DOOR_OPENED'];

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', AlertRuleConfig::class) ?? false;
    }

    public function rules(): array
    {
        $rules = ['rules' => ['required', 'array', 'size:6']];
        foreach (self::TYPES as $type) {
            $prefix = 'rules.'.$type;
            $rules[$prefix] = ['required', 'array:warning_threshold,critical_threshold,duration_minutes,is_enabled'];
            $rules[$prefix.'.warning_threshold'] = ['nullable', 'numeric', 'between:-40,100'];
            $rules[$prefix.'.critical_threshold'] = ['nullable', 'numeric', 'between:-40,100'];
            $rules[$prefix.'.duration_minutes'] = ['required', 'integer', 'between:0,1440'];
            $rules[$prefix.'.is_enabled'] = ['required', 'boolean'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $rules = $this->input('rules', []);
            if (! is_array($rules)) {
                return;
            }
            $high = $rules['HIGH_TEMPERATURE'] ?? [];
            if (! is_numeric($high['warning_threshold'] ?? null) || ! is_numeric($high['critical_threshold'] ?? null)) {
                $validator->errors()->add('rules.HIGH_TEMPERATURE.warning_threshold', 'Temperature warning and critical thresholds are required.');
            } elseif ((float) $high['critical_threshold'] <= (float) $high['warning_threshold']) {
                $validator->errors()->add('rules.HIGH_TEMPERATURE.critical_threshold', 'The critical temperature must exceed the warning temperature.');
            }
            if (! is_numeric($rules['LOW_TEMPERATURE']['warning_threshold'] ?? null)) {
                $validator->errors()->add('rules.LOW_TEMPERATURE.warning_threshold', 'The low-temperature threshold is required.');
            }
            $battery = $rules['LOW_BATTERY'] ?? [];
            if (! is_numeric($battery['warning_threshold'] ?? null) || ! is_numeric($battery['critical_threshold'] ?? null)) {
                $validator->errors()->add('rules.LOW_BATTERY.warning_threshold', 'Battery warning and critical thresholds are required.');
            } elseif ((float) $battery['critical_threshold'] >= (float) $battery['warning_threshold']) {
                $validator->errors()->add('rules.LOW_BATTERY.critical_threshold', 'The critical battery level must be below the warning level.');
            }
            if ((int) ($rules['DEVICE_OFFLINE']['duration_minutes'] ?? 0) < 1) {
                $validator->errors()->add('rules.DEVICE_OFFLINE.duration_minutes', 'The offline duration must be at least one minute.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $rules = $this->input('rules', []);
        if (is_array($rules)) {
            foreach (self::TYPES as $type) {
                if (isset($rules[$type]) && is_array($rules[$type])) {
                    $rules[$type]['is_enabled'] = filter_var($rules[$type]['is_enabled'] ?? false, FILTER_VALIDATE_BOOL);
                }
            }
            $this->merge(['rules' => $rules]);
        }
    }
}
