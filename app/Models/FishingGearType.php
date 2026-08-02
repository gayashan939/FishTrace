<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FishingGearType extends Model
{
    use HasUuids;

    protected $fillable = ['name', 'is_active'];

    public function catches(): HasMany
    {
        return $this->hasMany(CatchRecord::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
