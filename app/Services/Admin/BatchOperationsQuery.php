<?php

namespace App\Services\Admin;

use App\Models\FishBatch;
use App\Models\QrCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BatchOperationsQuery
{
    /** @return Collection<int, FishBatch> */
    public function exportRecords(array $filters): Collection
    {
        return $this->build($filters)->limit(10000)->get();
    }

    public function activeQrToken(FishBatch $batch): ?string
    {
        $token = QrCode::query()
            ->where('fish_batch_id', $batch->id)
            ->whereNull('revoked_at')
            ->value('public_token');

        return is_string($token) ? $token : null;
    }

    /** @return Builder<FishBatch> */
    public function build(array $filters): Builder
    {
        $sort = in_array($filters['sort'] ?? null, ['batch_code', 'status', 'total_weight_kg', 'created_at', 'created_from_catch_at'], true) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? null) === 'asc' ? 'asc' : 'desc';

        return FishBatch::query()
            ->with(['organization:id,name,code,type', 'species:id,common_name,scientific_name', 'latestAiPrediction'])
            ->withCount(['events', 'transportTrips', 'childLinks'])
            ->when($filters['q'] ?? null, function (Builder $query, string $term): void {
                $query->where(function (Builder $query) use ($term): void {
                    $query->where('batch_code', 'like', "%{$term}%")->orWhere('product_type', 'like', "%{$term}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['organization_id'] ?? null, fn (Builder $query, string $organizationId): Builder => $query->where('organization_id', $organizationId))
            ->when($filters['species_id'] ?? null, fn (Builder $query, string $speciesId): Builder => $query->where('fish_species_id', $speciesId))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type): Builder => $query->where('type', $type))
            ->when(isset($filters['is_recalled']), fn (Builder $query): Builder => $query->where('is_recalled', $filters['is_recalled']))
            ->when(isset($filters['is_public']), fn (Builder $query): Builder => $query->where('is_public', $filters['is_public']))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date): Builder => $query->where('created_at', '>=', $date.' 00:00:00'))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date): Builder => $query->where('created_at', '<=', $date.' 23:59:59'))
            ->orderBy($sort, $direction)
            ->orderBy('id');
    }
}
