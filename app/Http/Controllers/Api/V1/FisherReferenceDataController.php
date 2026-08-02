<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FishSpecies;
use App\Services\Fisher\FisherReferenceData;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class FisherReferenceDataController extends Controller
{
    public function __invoke(FisherReferenceData $referenceData): JsonResponse
    {
        $this->authorize('useInFisherWorkflow', FishSpecies::class);

        return ApiResponse::data($referenceData->get());
    }
}
