<?php

namespace App\Actions\Retail;

use App\Models\ColdChainAlert;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class ResolveRetailAlert
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $user, ColdChainAlert $alert, string $note): ColdChainAlert
    {
        return DB::transaction(function () use ($user, $alert, $note): ColdChainAlert {
            $locked = ColdChainAlert::query()->lockForUpdate()->findOrFail($alert->id);
            abort_unless(in_array($locked->status, ['OPEN', 'ACKNOWLEDGED'], true), 409, 'This alert is already resolved.');
            $old = ['status' => $locked->status, 'resolved_at' => $locked->resolved_at];
            $locked->update(['status' => 'RESOLVED', 'resolved_at' => now()]);
            $this->audit->record('RETAIL_ALERT_RESOLVED', $locked, $old, ['status' => 'RESOLVED', 'resolved_at' => $locked->resolved_at, 'note' => $note], $user, $user->primaryOrganization()?->id);

            return $locked->fresh() ?? $locked;
        });
    }
}
