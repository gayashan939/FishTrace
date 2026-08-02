<?php

namespace App\Actions\Batch;

use App\Enums\BatchStatus;
use App\Models\CatchRecord;
use App\Models\FishBatch;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateFishBatch
{
    public function execute(User $user, array $data): FishBatch
    {
        $organization = $user->primaryOrganization();
        if ($organization === null) {
            abort(403, 'An organization is required.');
        }

        return DB::transaction(function () use ($user, $organization, $data): FishBatch {
            $allocations = collect($data['catches']);
            $catches = CatchRecord::query()->where('organization_id', $organization->id)->whereIn('id', $allocations->pluck('catch_id'))->lockForUpdate()->get()->keyBy('id');
            if ($catches->count() !== $allocations->count()) {
                throw ValidationException::withMessages(['catches' => ['One or more catches are not available.']]);
            }
            $total = 0.0;
            foreach ($allocations as $allocation) {
                $catch = $catches[$allocation['catch_id']];
                if ($catch->fish_species_id !== $data['fish_species_id']) {
                    throw ValidationException::withMessages(['fish_species_id' => ['Every catch must match the batch species.']]);
                }
                $available = (float) $catch->weight_kg - (float) $catch->allocated_weight_kg;
                if ((float) $allocation['weight_kg'] > $available) {
                    throw ValidationException::withMessages(['catches' => ["Allocation exceeds the {$available} kg available for catch {$catch->id}."]]);
                }
                $total += (float) $allocation['weight_kg'];
            }
            $batch = FishBatch::create(['organization_id' => $organization->id, 'created_by' => $user->id, 'fish_species_id' => $data['fish_species_id'], 'batch_code' => 'FT-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)), 'type' => 'RAW', 'status' => BatchStatus::AVAILABLE_FOR_PROCESSING, 'product_type' => $data['product_type'], 'total_weight_kg' => $total, 'created_from_catch_at' => now()]);
            foreach ($allocations as $allocation) {
                $catch = $catches[$allocation['catch_id']];
                $batch->catches()->attach($catch->id, ['allocated_weight_kg' => $allocation['weight_kg']]);
                $catch->increment('allocated_weight_kg', $allocation['weight_kg']);
            }
            $batch->qrCode()->create(['public_token' => Str::random(64)]);
            $batch->events()->create(['organization_id' => $organization->id, 'actor_id' => $user->id, 'event_type' => 'BATCH_CREATED', 'title' => 'Batch created', 'public_data' => ['weight_kg' => $total], 'occurred_at' => now()]);

            return $batch->load(['species', 'qrCode', 'events']);
        });
    }
}
