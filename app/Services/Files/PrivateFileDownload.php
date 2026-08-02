<?php

namespace App\Services\Files;

use App\Models\FileAsset;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateFileDownload
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function download(FileAsset $file, string $auditAction, ?User $actor = null): BinaryFileResponse|StreamedResponse
    {
        $disk = Storage::disk($file->disk);
        abort_unless($disk->exists($file->path), 404, 'The stored file is unavailable.');

        $this->audit->record($auditAction, $file, null, [
            'original_name' => $file->original_name,
            'size_bytes' => $file->size_bytes,
        ], $actor);

        return $disk->download($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
