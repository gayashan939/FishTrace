<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Support\CreateSupportIssue;
use App\Http\Controllers\Controller;
use App\Http\Requests\Support\StoreSupportIssueRequest;
use App\Http\Resources\Support\SupportIssueResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SupportIssueController extends Controller
{
    public function store(StoreSupportIssueRequest $request, CreateSupportIssue $action): JsonResponse
    {
        $issue = $action->execute($request->user(), $request->validated());

        return ApiResponse::data(new SupportIssueResource($issue), 201);
    }
}
