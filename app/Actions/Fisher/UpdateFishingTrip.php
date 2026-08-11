<?php

namespace App\Actions\Fisher;

use App\Enums\FishingTripStatus;
use App\Models\Boat;
use App\Models\FishingTrip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateFishingTrip
{
    public function execute(User $user, FishingTrip $trip, array $attributes): FishingTrip
    {
        return DB::transaction(function () use ($user, $trip, $attributes): FishingTrip {
            $locked = FishingTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($locked->hasStatus(FishingTripStatus::DRAFT), 409, 'Only a draft trip can be edited.');
            $boat = Boat::query()->where('organization_id', $locked->organization_id)->where('owner_id', $user->id)->where('is_active', true)->lockForUpdate()->findOrFail($attributes['boat_id']);
            $crew = $attributes['crew'] ?? null;
            unset($attributes['crew']);
            $locked->update(array_merge($attributes, ['boat_id' => $boat->id]));
            if (is_array($crew)) {
                $locked->crewMembers()->delete();
                $locked->crewMembers()->createMany(array_map(fn (string $name): array => ['name' => $name], $crew));
            }

            return $locked->fresh(['boat', 'landingSite', 'crewMembers']) ?? $locked;
        });
    }
}
