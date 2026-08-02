<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QualityGrade extends Model
{
    use HasUuids;

    protected $fillable = ['code', 'name', 'rank', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
