<?php

namespace App\Actions\Transport;

use App\Enums\NotificationType;
use App\Enums\TransportTripStatus;
use App\Models\DeviceAssignment;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Notifications\OperationalNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StartTransportTrip
{
    public function __construct(private OperationalNotifier $notifier, private AuditLogger $audit) {}

    public function execute(User $user, TransportTrip $trip): TransportTrip
    {
        $trip = DB::transaction(function () use ($trip): TransportTrip {
            $locked = TransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($locked->hasStatus(TransportTripStatus::DRAFT) || $locked->hasStatus(TransportTripStatus::READY), 409, 'Trip cannot be started.');
            abort_unless($locked->vehicle()->where('is_active', true)->exists(), 409, 'The assigned vehicle is inactive.');
            abort_unless($locked->batches()->exists(), 409, 'Assign at least one batch.');
            abort_unless($locked->checklist()->whereNotNull('completed_at')->exists(), 409, 'Complete every mandatory pre-trip checklist item.');
            $assignment = DeviceAssignment::query()->where('transport_trip_id', $locked->id)->where('status', 'ACTIVE')->latest('assigned_at')->lockForUpdate()->first();
            abort_unless($assignment?->firebase_sync_status === 'SYNCED', 409, 'Firebase device assignment is not synchronized.');
            abort_if($assignment->expires_at !== null && Carbon::parse($assignment->expires_at)->isPast(), 409, 'The device assignment has expired.');
            $locked->update(['status' => TransportTripStatus::ACTIVE, 'started_at' => now()]);
            $locked->batches()->update(['status' => 'IN_TRANSPORT']);

            return $locked;
        });
        $this->audit->record('TRANSPORT_TRIP_STARTED', $trip, ['status' => TransportTripStatus::READY->value], ['status' => TransportTripStatus::ACTIVE->value], $user, $trip->organization_id);
        $this->notifier->organization($trip->organization_id, NotificationType::TRANSPORT_STARTED, 'Transport started', $trip->trip_code.' has started.', ['transport_trip_id' => $trip->id]);

        return $trip->fresh(['vehicle', 'batches', 'activeAssignment.device', 'checklist.items']);
    }
}
