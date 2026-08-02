<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingSite extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'district', 'is_active'];

    public function trips(): HasMany
    {
        return $this->hasMany(FishingTrip::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
