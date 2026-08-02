<?php

namespace App\Actions\Batch;

use App\Enums\BatchStatus;
use App\Models\ChildBatch;
use App\Models\FishBatch;
use App\Models\PackageLabel;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SplitFishBatch
{
    /** @return Collection<int, FishBatch> */
    public function execute(User $user, FishBatch $parent, array $data): Collection
    {
        return DB::transaction(function () use ($user, $parent, $data): Collection {
            $locked = FishBatch::query()->lockForUpdate()->findOrFail($parent->id);
            abort_unless(in_array($locked->getRawOriginal('status'), [BatchStatus::PROCESSED->value, BatchStatus::READY_FOR_TRANSPORT->value], true), 409, 'Only a processed batch can be split.');
            $alreadyAllocated = (float) ChildBatch::query()->where('parent_batch_id', $locked->id)->sum('allocated_weight_kg');
            $requested = collect($data['children'])->sum(fn (array $child): float => (float) $child['weight_kg']);
            abort_if($alreadyAllocated + $requested > (float) $locked->total_weight_kg + 0.001, 422, 'Child batch weights exceed the available parent weight.');
            $organization = $user->primaryOrganization();
            abort_unless($organization !== null, 403);
            $children = collect();
            $existingChildren = ChildBatch::where('parent_batch_id', $locked->id)->count();
            foreach ($data['children'] as $index => $childData) {
                $child = FishBatch::create(['organization_id' => $organization->id, 'created_by' => $user->id, 'fish_species_id' => $locked->fish_species_id, 'batch_code' => $locked->batch_code.'-'.str_pad((string) ($existingChildren + $index + 1), 2, '0', STR_PAD_LEFT), 'type' => 'CHILD', 'status' => BatchStatus::PROCESSED, 'product_type' => $childData['product_type'] ?? $locked->product_type, 'total_weight_kg' => $childData['weight_kg'], 'created_from_catch_at' => $locked->created_from_catch_at]);
                ChildBatch::create(['parent_batch_id' => $locked->id, 'child_batch_id' => $child->id, 'allocated_weight_kg' => $childData['weight_kg'], 'created_by' => $user->id]);
                DB::table('batch_relationships')->insert(['id' => (string) Str::uuid(), 'parent_batch_id' => $locked->id, 'child_batch_id' => $child->id, 'relationship_type' => 'SPLIT', 'created_at' => now(), 'updated_at' => now()]);
                $token = Str::random(64);
                $child->qrCode()->create(['public_token' => $token]);
                PackageLabel::create(['fish_batch_id' => $child->id, 'label_code' => 'LBL-'.Str::upper(Str::random(12)), 'public_token' => $token, 'package_weight_kg' => (float) $childData['weight_kg'] / (int) $childData['package_count'], 'package_count' => $childData['package_count']]);
                $child->events()->create(['organization_id' => $organization->id, 'actor_id' => $user->id, 'event_type' => 'CHILD_BATCH_CREATED', 'title' => 'Child batch created', 'public_data' => ['parent_batch_code' => $locked->batch_code, 'weight_kg' => (float) $childData['weight_kg']], 'occurred_at' => now()]);
                $children->push($child->load(['species', 'qrCode']));
            }
            $locked->events()->create(['organization_id' => $organization->id, 'actor_id' => $user->id, 'event_type' => 'BATCH_SPLIT', 'title' => 'Batch split into child batches', 'public_data' => ['child_count' => $children->count(), 'allocated_weight_kg' => $requested], 'occurred_at' => now()]);

            return $children;
        });
    }
}
