<?php

namespace App\Services\Fisher;

use App\Models\Boat;
use App\Models\CatchRecord;
use App\Models\FishBatch;
use App\Models\FishingTrip;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class FisherOperationsQuery
{
    public function boats(User $user, int $perPage): LengthAwarePaginator
    {
        return Boat::query()->where('organization_id', $user->primaryOrganization()?->id)->latest()->paginate($perPage);
    }

    public function trips(User $user, int $perPage): LengthAwarePaginator
    {
        return FishingTrip::query()->with('boat')->where('organization_id', $user->primaryOrganization()?->id)->latest()->paginate($perPage);
    }

    public function catches(User $user, int $perPage): LengthAwarePaginator
    {
        return CatchRecord::query()->with(['trip', 'species'])->where('organization_id', $user->primaryOrganization()?->id)->latest()->paginate($perPage);
    }

    public function batches(User $user, int $perPage): LengthAwarePaginator
    {
        return FishBatch::query()->with('species')->where('organization_id', $user->primaryOrganization()?->id)->latest()->paginate($perPage);
    }

    public function trip(FishingTrip $trip): FishingTrip
    {
        return $trip->load(['boat', 'catches.species']);
    }

    public function catch(CatchRecord $catch): CatchRecord
    {
        return $catch->load(['trip', 'species', 'batches']);
    }

    public function batch(FishBatch $batch): FishBatch
    {
        return $batch->load(['species', 'catches', 'qrCode', 'events']);
    }

    public function timeline(FishBatch $batch): LengthAwarePaginator
    {
        return $batch->events()->paginate(50);
    }

    public function qrToken(FishBatch $batch): ?string
    {
        $token = $batch->qrCode()->value('public_token');

        return is_string($token) ? $token : null;
    }
}
