<?php

namespace App\Models;

use App\Enums\FileCategory;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileAsset extends Model
{
    use BelongsToOrganization, HasUuids;

    protected $fillable = ['organization_id', 'uploaded_by', 'category', 'entity_type', 'entity_id', 'disk', 'path', 'original_name', 'mime_type', 'extension', 'size_bytes', 'sha256'];

    protected $hidden = ['disk', 'path'];

    protected function casts(): array
    {
        return ['category' => FileCategory::class, 'size_bytes' => 'integer'];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
