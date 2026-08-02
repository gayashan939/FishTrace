<?php

namespace App\Services\Fisher;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\FishBatch;
use App\Models\FishingTrip;
use App\Models\User;

class FisherDashboard
{
    public function get(User $user): array
    {
        $organization = $user->primaryOrganization();
        abort_unless($organization !== null, 403, 'An organization is required.');
        $tripQuery = FishingTrip::query()->where('organization_id', $organization->id)->where('fisher_id', $user->id);
        $catchQuery = CatchRecord::query()->where('organization_id', $organization->id)->whereHas('trip', fn ($query) => $query->where('fisher_id', $user->id));
        $totals = (clone $catchQuery)->selectRaw('COUNT(*) AS catch_count, COALESCE(SUM(weight_kg),0) AS total_weight, COALESCE(SUM(allocated_weight_kg),0) AS allocated_weight')->first();

        return [
            'trip_counts' => (clone $tripQuery)->selectRaw('status, COUNT(*) AS aggregate')->groupBy('status')->pluck('aggregate', 'status'),
            'active_trip' => (clone $tripQuery)->where('status', 'ACTIVE')->with(['boat:id,name,registration_number', 'landingSite:id,name,district'])->withCount('catches')->latest('departed_at')->first(),
            'boats' => ['total' => Boat::query()->where('organization_id', $organization->id)->where('owner_id', $user->id)->count(), 'active' => Boat::query()->where('organization_id', $organization->id)->where('owner_id', $user->id)->where('is_active', true)->count()],
            'catches' => ['count' => (int) ($totals?->getAttribute('catch_count') ?? 0), 'total_weight_kg' => (float) ($totals?->getAttribute('total_weight') ?? 0), 'allocated_weight_kg' => (float) ($totals?->getAttribute('allocated_weight') ?? 0), 'available_weight_kg' => max(0, (float) ($totals?->getAttribute('total_weight') ?? 0) - (float) ($totals?->getAttribute('allocated_weight') ?? 0))],
            'batch_count' => FishBatch::query()->where('organization_id', $organization->id)->where('created_by', $user->id)->count(),
            'recent_trips' => (clone $tripQuery)->with(['boat:id,name,registration_number', 'landingSite:id,name,district'])->withCount('catches')->latest()->limit(5)->get(),
        ];
    }
}
