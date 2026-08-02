<?php

namespace App\Actions\Fisher;

use App\Enums\FishingTripStatus;
use App\Models\Boat;
use App\Models\FishingTrip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateFishingTrip
{
    public function execute(User $user, array $attributes): FishingTrip
    {
        $organization = $user->primaryOrganization();
        abort_unless($organization !== null, 403, 'An organization is required.');

        return DB::transaction(function () use ($user, $organization, $attributes): FishingTrip {
            $boat = Boat::query()->where('organization_id', $organization->id)->where('owner_id', $user->id)->where('is_active', true)->lockForUpdate()->findOrFail($attributes['boat_id']);

            return FishingTrip::create(array_merge($attributes, ['organization_id' => $organization->id, 'fisher_id' => $user->id, 'boat_id' => $boat->id, 'trip_code' => 'FTR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)), 'status' => FishingTripStatus::DRAFT]));
        });
    }
}
