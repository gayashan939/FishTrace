<?php

namespace App\Services\Transport;

use App\Enums\TransportTripStatus;
use App\Models\ChecklistItem;
use App\Models\PreTripChecklist;
use App\Models\TransportTrip;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class TransportChecklistService
{
    /** @var array<string, string> */
    public const ITEMS = [
        'vehicle_inspected' => 'Vehicle safety inspection completed',
        'refrigeration_operational' => 'Refrigeration system is operational',
        'cargo_secured' => 'Cargo is secured and batch seals are intact',
        'device_online' => 'IoT device is powered and reporting',
        'doors_sealed' => 'Cargo doors are closed and sealed',
    ];

    public function __construct(private AuditLogger $audit) {}

    public function initialize(TransportTrip $trip): PreTripChecklist
    {
        return DB::transaction(function () use ($trip): PreTripChecklist {
            $checklist = PreTripChecklist::query()->firstOrCreate(['transport_trip_id' => $trip->id]);
            foreach (self::ITEMS as $key => $label) {
                $checklist->items()->firstOrCreate(['item_key' => $key], ['label' => $label, 'is_mandatory' => true]);
            }

            return $checklist->load('items');
        });
    }

    public function get(TransportTrip $trip): PreTripChecklist
    {
        $checklist = PreTripChecklist::query()->with('items')->where('transport_trip_id', $trip->id)->first();
        abort_unless($checklist !== null, 404, 'The pre-trip checklist is unavailable.');

        return $checklist;
    }

    /** @param array<string, bool> $items */
    public function update(User $user, TransportTrip $trip, array $items): PreTripChecklist
    {
        $checklist = DB::transaction(function () use ($user, $trip, $items): PreTripChecklist {
            $lockedTrip = TransportTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($lockedTrip->hasStatus(TransportTripStatus::DRAFT) || $lockedTrip->hasStatus(TransportTripStatus::READY), 409, 'The checklist can only be changed before departure.');
            $checklist = PreTripChecklist::query()->where('transport_trip_id', $lockedTrip->id)->lockForUpdate()->first();
            $checklist ??= $this->initialize($lockedTrip);
            $checklistItems = ChecklistItem::query()->where('pre_trip_checklist_id', $checklist->id)->get();
            foreach ($checklistItems as $item) {
                $completed = $items[$item->item_key] ?? $item->is_completed;
                $item->update([
                    'is_completed' => $completed,
                    'completed_by' => $completed ? $user->id : null,
                    'completed_at' => $completed ? now() : null,
                ]);
            }
            $checklist->setRelation('items', $checklistItems);
            $complete = ! $checklist->items()->where('is_mandatory', true)->where('is_completed', false)->exists();
            $checklist->update(['completed_by' => $complete ? $user->id : null, 'completed_at' => $complete ? now() : null]);
            $lockedTrip->update(['status' => $complete ? TransportTripStatus::READY : TransportTripStatus::DRAFT]);

            return $checklist->load('items');
        });

        $this->audit->record('TRANSPORT_CHECKLIST_UPDATED', $checklist, null, ['completed' => $checklist->completed_at !== null, 'completed_items' => $checklist->items->where('is_completed', true)->count()], $user, $trip->organization_id);

        return $checklist;
    }
}
