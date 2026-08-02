<?php

namespace App\Actions\Files;

use App\Models\FileAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteFileAsset
{
    public function execute(FileAsset $file): void
    {
        $asset = FileAsset::query()->findOrFail($file->id);
        $disk = Storage::disk($asset->disk);
        if ($disk->exists($asset->path)) {
            abort_unless($disk->delete($asset->path), 500, 'The stored file could not be removed.');
        }

        DB::transaction(function () use ($asset): void {
            $locked = FileAsset::query()->lockForUpdate()->findOrFail($asset->id);
            $locked->delete();
        });
    }
}
