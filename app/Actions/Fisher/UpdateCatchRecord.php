<?php

namespace App\Actions\Fisher;

use App\Enums\FishingTripStatus;
use App\Models\CatchRecord;
use App\Models\FishingTrip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateCatchRecord
{
    public function execute(User $user, CatchRecord $record, array $attributes): CatchRecord
    {
        return DB::transaction(function () use ($record, $attributes): CatchRecord {
            $locked = CatchRecord::query()->lockForUpdate()->findOrFail($record->id);
            $trip = FishingTrip::query()->lockForUpdate()->findOrFail($locked->fishing_trip_id);
            abort_unless($trip->hasStatus(FishingTripStatus::ACTIVE), 409, 'Catches can only be edited while the trip is active.');
            $pivotAllocated = (float) DB::table('batch_catches')->where('catch_record_id', $locked->id)->sum('allocated_weight_kg');
            $allocated = max((float) $locked->allocated_weight_kg, $pivotAllocated);
            if ((float) $attributes['weight_kg'] < $allocated) {
                throw ValidationException::withMessages(['weight_kg' => ['Catch weight cannot be lower than the '.$allocated.' kg already allocated to batches.']]);
            }
            if ($allocated > 0 && $attributes['fish_species_id'] !== $locked->fish_species_id) {
                throw ValidationException::withMessages(['fish_species_id' => ['Species cannot change after catch weight has been allocated to a batch.']]);
            }
            $locked->update($attributes);

            return $locked->fresh(['trip', 'species', 'gearType', 'batches']) ?? $locked;
        });
    }
}
