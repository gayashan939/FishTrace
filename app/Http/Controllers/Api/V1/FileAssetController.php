<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Files\DeleteFileAsset;
use App\Actions\Files\StoreFileAsset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Files\DeleteFileRequest;
use App\Http\Requests\Files\StoreFileRequest;
use App\Http\Resources\Files\FileAssetResource;
use App\Models\FileAsset;
use App\Services\Files\PrivateFileDownload;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileAssetController extends Controller
{
    public function store(StoreFileRequest $request, StoreFileAsset $action): JsonResponse
    {
        return ApiResponse::data(new FileAssetResource($action->execute($request->user(), $request->validated())), 201);
    }

    public function show(FileAsset $file, PrivateFileDownload $download): BinaryFileResponse|StreamedResponse
    {
        $this->authorize('view', $file);

        return $download->download($file, 'FILE_DOWNLOADED');
    }

    public function destroy(DeleteFileRequest $request, FileAsset $file, DeleteFileAsset $action): Response
    {
        $action->execute($file);

        return response()->noContent();
    }
}
