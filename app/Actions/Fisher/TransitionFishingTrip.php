<?php

namespace App\Actions\Fisher;

use App\Enums\FishingTripStatus;
use App\Models\FishingTrip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransitionFishingTrip
{
    public function start(User $user, FishingTrip $trip): FishingTrip
    {
        return $this->transition($trip, FishingTripStatus::DRAFT, FishingTripStatus::ACTIVE, ['departed_at' => now()], 'Only a draft trip can be started.');
    }

    public function complete(User $user, FishingTrip $trip): FishingTrip
    {
        return $this->transition($trip, FishingTripStatus::ACTIVE, FishingTripStatus::COMPLETED, ['returned_at' => now()], 'Only an active trip can be completed.');
    }

    public function cancel(User $user, FishingTrip $trip): FishingTrip
    {
        return $this->transition($trip, FishingTripStatus::DRAFT, FishingTripStatus::CANCELLED, [], 'Only a draft trip can be cancelled.');
    }

    private function transition(FishingTrip $trip, FishingTripStatus $from, FishingTripStatus $to, array $attributes, string $message): FishingTrip
    {
        return DB::transaction(function () use ($trip, $from, $to, $attributes, $message): FishingTrip {
            $locked = FishingTrip::query()->lockForUpdate()->findOrFail($trip->id);
            abort_unless($locked->hasStatus($from), 409, $message);
            if ($to === FishingTripStatus::ACTIVE) {
                abort_unless($locked->boat()->where('is_active', true)->exists(), 409, 'The assigned boat must be active before departure.');
            }
            $locked->update(array_merge($attributes, ['status' => $to]));

            return $locked->fresh(['boat', 'landingSite', 'crewMembers']) ?? $locked;
        });
    }
}
