<?php

namespace App\Actions\Admin;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SystemSettings;
use Illuminate\Support\Facades\DB;

class UpdateSystemSettings
{
    public function __construct(private AuditLogger $audit, private SystemSettings $settings) {}

    public function execute(User $actor, array $values): void
    {
        DB::transaction(function () use ($actor, $values): void {
            foreach ($values as $key => $value) {
                $setting = SystemSetting::query()->lockForUpdate()->firstOrNew(['key' => $key]);
                $oldValue = $setting->exists ? $setting->value : $this->settings->get($key);
                $setting->fill(['value' => (string) $value, 'updated_by' => $actor->id])->save();
                $this->audit->record('SYSTEM_SETTING_UPDATED', $setting, ['value' => $oldValue], ['value' => (string) $value], $actor);
                $this->settings->forget($key);
            }
        });
    }
}
