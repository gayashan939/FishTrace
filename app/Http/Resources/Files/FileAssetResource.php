<?php

namespace App\Http\Resources\Files;

use App\Models\FileAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class FileAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $asset = $this->resource;
        if (! $asset instanceof FileAsset) {
            throw new LogicException('FileAssetResource requires a FileAsset model.');
        }

        return [
            'id' => $asset->id,
            'category' => $asset->category,
            'entity_type' => $asset->entity_type,
            'entity_id' => $asset->entity_id,
            'original_name' => $asset->original_name,
            'mime_type' => $asset->mime_type,
            'extension' => $asset->extension,
            'size_bytes' => $asset->size_bytes,
            'sha256' => $asset->sha256,
            'download_url' => route('files.show', $asset->id),
            'created_at' => $asset->created_at,
        ];
    }
}
