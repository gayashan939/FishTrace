<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use App\Services\Consumer\PublicTraceView;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TraceController extends Controller
{
    public function show(string $qrToken, PublicTraceView $trace): View
    {
        return view('consumer.trace', ['trace' => $trace->build($qrToken)]);
    }

    public function api(string $qrToken, PublicTraceView $trace): JsonResponse
    {
        return ApiResponse::data($trace->build($qrToken));
    }
}
