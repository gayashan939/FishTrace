<?php

namespace App\Actions\Fisher;

use App\Enums\FishingTripStatus;
use App\Models\CatchRecord;
use App\Models\FishingTrip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteCatchRecord
{
    public function execute(User $user, CatchRecord $record): void
    {
        DB::transaction(function () use ($record): void {
            $locked = CatchRecord::query()->lockForUpdate()->findOrFail($record->id);
            $trip = FishingTrip::query()->lockForUpdate()->findOrFail($locked->fishing_trip_id);
            abort_unless($trip->hasStatus(FishingTripStatus::ACTIVE), 409, 'Catches can only be deleted while the trip is active.');
            $pivotAllocated = (float) DB::table('batch_catches')->where('catch_record_id', $locked->id)->sum('allocated_weight_kg');
            abort_if(max((float) $locked->allocated_weight_kg, $pivotAllocated) > 0, 409, 'Allocated catches cannot be deleted.');
            $locked->delete();
        });
    }
}
