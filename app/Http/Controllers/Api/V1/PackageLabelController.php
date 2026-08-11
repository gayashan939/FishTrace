<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Processor\PackageLabelResource;
use App\Models\PackageLabel;
use App\Services\Processing\ProcessingOperationsQuery;
use App\Services\Traceability\QrCodeRenderer;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PackageLabelController extends Controller
{
    public function show(PackageLabel $label, ProcessingOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $label);

        return ApiResponse::data(new PackageLabelResource($operations->label($label)));
    }

    public function print(PackageLabel $label, ProcessingOperationsQuery $operations, QrCodeRenderer $renderer): Response
    {
        $this->authorize('view', $label);
        $label = $operations->label($label);

        return response()->view('processor.package-label', [
            'label' => $label,
            'batch' => $label->batch,
            'qrSvg' => $renderer->traceSvg((string) $label->getRawOriginal('public_token')),
        ]);
    }
}
