<?php

namespace App\Console\Commands;

use App\Models\DeviceAssignment;
use App\Services\Firebase\FirebaseAssignmentSynchronizer;
use Illuminate\Console\Command;

class ReconcileFirebaseAssignments extends Command
{
    protected $signature = 'fishtrace:reconcile-firebase-assignments {--limit=100}';

    protected $description = 'Retry pending and failed Firebase device assignment mirrors';

    public function handle(FirebaseAssignmentSynchronizer $synchronizer): int
    {
        $limit = min(500, max(1, (int) $this->option('limit')));
        $assignments = DeviceAssignment::query()->whereIn('firebase_sync_status', ['PENDING', 'FAILED'])->oldest()->limit($limit)->get();
        $synchronized = 0;
        foreach ($assignments as $assignment) {
            $synchronized += $synchronizer->attempt($assignment) ? 1 : 0;
        }
        $this->info("Reconciled {$synchronized} of {$assignments->count()} Firebase assignments.");

        return $synchronized === $assignments->count() ? self::SUCCESS : self::FAILURE;
    }
}
