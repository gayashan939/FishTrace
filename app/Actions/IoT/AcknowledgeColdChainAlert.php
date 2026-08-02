<?php

namespace App\Actions\IoT;

use App\Models\ColdChainAlert;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AcknowledgeColdChainAlert
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $actor, ColdChainAlert $alert, ?string $note): ColdChainAlert
    {
        return DB::transaction(function () use ($actor, $alert, $note): ColdChainAlert {
            $locked = ColdChainAlert::query()->lockForUpdate()->findOrFail($alert->id);
            abort_unless($locked->status === 'OPEN', 409, 'Only an open alert can be acknowledged.');
            $locked->update(['status' => 'ACKNOWLEDGED']);
            DB::table('alert_acknowledgements')->insert(['id' => (string) Str::uuid(), 'cold_chain_alert_id' => $locked->id, 'user_id' => $actor->id, 'note' => $note, 'acknowledged_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
            $this->audit->record('COLD_CHAIN_ALERT_ACKNOWLEDGED', $locked, ['status' => 'OPEN'], ['status' => 'ACKNOWLEDGED'], $actor);

            return $locked->fresh() ?? $locked;
        });
    }
}
