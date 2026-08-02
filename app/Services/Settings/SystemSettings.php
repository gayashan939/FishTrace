<?php

namespace App\Services\Settings;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;

class SystemSettings
{
    public const DEFAULTS = [
        'platform_name' => 'FishTrace',
        'support_email' => '',
        'consumer_portal_enabled' => '1',
        'consumer_portal_notice' => '',
        'telemetry_retention_hours' => '48',
    ];

    public function all(): array
    {
        return collect(self::DEFAULTS)->mapWithKeys(fn (string $default, string $key): array => [$key => $this->get($key)])->all();
    }

    public function get(string $key, ?string $default = null): string
    {
        if (! array_key_exists($key, self::DEFAULTS)) {
            throw new \InvalidArgumentException('Unknown system setting.');
        }

        $fallback = $default ?? $this->defaultValue($key);

        return Cache::remember('fishtrace:setting:'.$key, now()->addMinutes(5), fn (): string => (string) (SystemSetting::query()->where('key', $key)->value('value') ?? $fallback));
    }

    public function boolean(string $key): bool
    {
        return filter_var($this->get($key), FILTER_VALIDATE_BOOL);
    }

    public function integer(string $key): int
    {
        return (int) $this->get($key);
    }

    public function forget(string $key): void
    {
        Cache::forget('fishtrace:setting:'.$key);
    }

    private function defaultValue(string $key): string
    {
        return $key === 'telemetry_retention_hours'
            ? (string) config('fishtrace.firebase.telemetry_retention_hours', 48)
            : self::DEFAULTS[$key];
    }
}
