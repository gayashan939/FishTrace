<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FishSpecies extends Model
{
    use HasUuids;

    protected $fillable = ['common_name', 'scientific_name', 'is_active'];

    public function catches(): HasMany
    {
        return $this->hasMany(CatchRecord::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(FishBatch::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
