<?php

namespace App\Models;

use App\Enums\BatchStatus;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FishBatch extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'created_by', 'fish_species_id', 'fishing_trip_id', 'batch_code', 'type', 'status', 'product_type', 'total_weight_kg', 'fish_count', 'quality_grade', 'storage_temperature_celsius', 'ice_type', 'ice_amount_kg', 'landing_site_name', 'notes', 'created_from_catch_at', 'is_public', 'is_recalled'];

    protected function casts(): array
    {
        return ['status' => BatchStatus::class, 'total_weight_kg' => 'decimal:3', 'fish_count' => 'integer', 'storage_temperature_celsius' => 'decimal:2', 'ice_amount_kg' => 'decimal:3', 'is_public' => 'boolean', 'is_recalled' => 'boolean', 'created_from_catch_at' => 'datetime'];
    }

    public function fishingTrip(): BelongsTo
    {
        return $this->belongsTo(FishingTrip::class);
    }

    public function species(): BelongsTo
    {
        return $this->belongsTo(FishSpecies::class, 'fish_species_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function catches(): BelongsToMany
    {
        return $this->belongsToMany(CatchRecord::class, 'batch_catches')->withPivot('allocated_weight_kg');
    }

    public function qrCode(): HasOne
    {
        return $this->hasOne(QrCode::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TraceabilityEvent::class)->orderBy('occurred_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(FileAsset::class, 'entity_id')
            ->where('entity_type', 'fish_batch')
            ->whereIn('category', ['BATCH_DOCUMENT', 'CERTIFICATE']);
    }

    public function intakes(): HasMany
    {
        return $this->hasMany(BatchIntake::class);
    }

    public function processingRecord(): HasOne
    {
        return $this->hasOne(ProcessingRecord::class);
    }

    public function childLinks(): HasMany
    {
        return $this->hasMany(ChildBatch::class, 'parent_batch_id');
    }

    public function parentLinks(): HasMany
    {
        return $this->hasMany(ChildBatch::class, 'child_batch_id');
    }

    public function transportTrips(): BelongsToMany
    {
        return $this->belongsToMany(TransportTrip::class, 'transport_batches');
    }

    public function aiPredictions(): HasMany
    {
        return $this->hasMany(AIPrediction::class);
    }

    public function latestAiPrediction(): HasOne
    {
        return $this->hasOne(AIPrediction::class)->latestOfMany('predicted_at');
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(QualityInspection::class);
    }

    public function packageLabels(): HasMany
    {
        return $this->hasMany(PackageLabel::class);
    }

    public function inventoryLots(): HasMany
    {
        return $this->hasMany(InventoryLot::class);
    }
}
