<?php

namespace App\Services\IoT;

use App\Models\AlertRuleConfig;

class AlertRuleResolver
{
    public const DEFAULTS = [
        'HIGH_TEMPERATURE' => ['warning_threshold' => 4.0, 'critical_threshold' => 8.0, 'duration_minutes' => 10, 'is_enabled' => true],
        'LOW_TEMPERATURE' => ['warning_threshold' => 0.0, 'critical_threshold' => null, 'duration_minutes' => 0, 'is_enabled' => true],
        'LOW_BATTERY' => ['warning_threshold' => 20.0, 'critical_threshold' => 10.0, 'duration_minutes' => 0, 'is_enabled' => true],
        'DEVICE_OFFLINE' => ['warning_threshold' => null, 'critical_threshold' => null, 'duration_minutes' => 15, 'is_enabled' => true],
        'GPS_UNAVAILABLE' => ['warning_threshold' => null, 'critical_threshold' => null, 'duration_minutes' => 0, 'is_enabled' => true],
        'DOOR_OPENED' => ['warning_threshold' => null, 'critical_threshold' => null, 'duration_minutes' => 0, 'is_enabled' => true],
    ];

    public function resolve(?string $organizationId, string $type): array
    {
        $default = self::DEFAULTS[$type] ?? throw new \InvalidArgumentException('Unknown alert rule type.');
        $rule = AlertRuleConfig::query()
            ->where('rule_type', $type)
            ->where(fn ($query) => $query->where('organization_id', $organizationId)->orWhereNull('organization_id'))
            ->orderByRaw('CASE WHEN organization_id IS NULL THEN 1 ELSE 0 END')
            ->first();

        return $rule === null ? $default : [
            'warning_threshold' => $rule->warning_threshold === null ? null : (float) $rule->warning_threshold,
            'critical_threshold' => $rule->critical_threshold === null ? null : (float) $rule->critical_threshold,
            'duration_minutes' => $rule->duration_minutes,
            'is_enabled' => $rule->is_enabled,
        ];
    }

    public function globalRules(): array
    {
        return collect(self::DEFAULTS)->map(function (array $defaults, string $type): array {
            $rule = AlertRuleConfig::query()->where('scope_key', 'GLOBAL:'.$type)->first();

            return array_merge(['rule_type' => $type], $defaults, $rule?->only(['warning_threshold', 'critical_threshold', 'duration_minutes', 'is_enabled']) ?? []);
        })->values()->all();
    }
}
