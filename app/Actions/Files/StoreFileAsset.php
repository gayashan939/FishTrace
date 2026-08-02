<?php

namespace App\Actions\Files;

use App\Enums\FileCategory;
use App\Models\FileAsset;
use App\Models\User;
use App\Services\Files\AttachmentAuthorizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class StoreFileAsset
{
    public function __construct(private AttachmentAuthorizer $authorizer) {}

    public function execute(User $user, array $data): FileAsset
    {
        $category = FileCategory::from($data['category']);
        $this->authorizer->authorize($user, $category, $data['entity_type'], $data['entity_id']);
        $organization = $user->primaryOrganization();
        abort_unless($organization !== null, 403);
        $upload = $data['file'];
        abort_unless($upload instanceof UploadedFile, 422, 'A valid uploaded file is required.');
        $extension = mb_strtolower($upload->extension());
        $disk = (string) config('filesystems.default', 'local');
        $directory = 'organizations/'.$organization->id.'/'.mb_strtolower($category->value).'/'.now()->format('Y/m');
        $name = Str::uuid().'.'.$extension;
        $path = $upload->storeAs($directory, $name, $disk);
        abort_if($path === false, 500, 'The file could not be stored.');

        try {
            return FileAsset::create([
                'organization_id' => $organization->id,
                'uploaded_by' => $user->id,
                'category' => $category,
                'entity_type' => $data['entity_type'],
                'entity_id' => $data['entity_id'],
                'disk' => $disk,
                'path' => $path,
                'original_name' => basename($upload->getClientOriginalName()),
                'mime_type' => (string) $upload->getMimeType(),
                'extension' => $extension,
                'size_bytes' => $upload->getSize(),
                'sha256' => hash_file('sha256', $upload->getRealPath()),
            ]);
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
    }
}
