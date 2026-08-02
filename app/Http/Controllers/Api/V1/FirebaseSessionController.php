<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Firebase\IssueFirebaseSession;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FirebaseSessionController extends Controller
{
    public function __invoke(Request $request, IssueFirebaseSession $action): JsonResponse
    {
        return ApiResponse::data($action->execute($request->user()));
    }
}
