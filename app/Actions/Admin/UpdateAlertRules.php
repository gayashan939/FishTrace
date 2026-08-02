<?php

namespace App\Actions\Admin;

use App\Models\AlertRuleConfig;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class UpdateAlertRules
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $actor, array $rules): void
    {
        DB::transaction(function () use ($actor, $rules): void {
            foreach ($rules as $type => $attributes) {
                $scopeKey = 'GLOBAL:'.$type;
                $rule = AlertRuleConfig::query()->lockForUpdate()->firstOrNew(['scope_key' => $scopeKey]);
                $old = $rule->exists ? $rule->getAttributes() : null;
                $rule->fill(array_merge($attributes, ['organization_id' => null, 'scope_key' => $scopeKey, 'rule_type' => $type]))->save();
                $this->audit->record('ALERT_RULE_UPDATED', $rule, $old, $rule->getAttributes(), $actor);
            }
        });
    }
}
