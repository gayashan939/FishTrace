<?php

namespace App\Actions\Fisher;

use App\Enums\FishingTripStatus;
use App\Models\CatchRecord;
use App\Models\FishingTrip;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreateCatchRecord
{
    public function execute(User $user, array $data): CatchRecord
    {
        $organization = $user->primaryOrganization();
        if ($organization === null) {
            abort(403, 'An organization is required.');
        }
        if (! empty($data['client_record_id'])) {
            $existing = CatchRecord::query()->where('organization_id', $organization->id)->where('client_record_id', $data['client_record_id'])->first();
            if ($existing) {
                return $existing->load(['trip', 'species']);
            }
        }
        $trip = FishingTrip::query()->where('organization_id', $organization->id)->where('fisher_id', $user->id)->findOrFail($data['fishing_trip_id']);
        if (! $trip->hasStatus(FishingTripStatus::ACTIVE)) {
            throw ValidationException::withMessages(['fishing_trip_id' => ['Catches may only be added to an active trip.']]);
        }

        return CatchRecord::create(array_merge($data, ['organization_id' => $organization->id]))->load(['trip', 'species']);
    }
}
