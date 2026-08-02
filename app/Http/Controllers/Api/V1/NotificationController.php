<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\NotificationFilterRequest;
use App\Http\Requests\Notifications\NotificationMutationRequest;
use App\Http\Resources\Notifications\NotificationResource;
use App\Services\Notifications\NotificationOperations;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(NotificationFilterRequest $request, NotificationOperations $notifications): JsonResponse
    {
        $page = $notifications->directory($request->user(), $request->validated());
        $page->setCollection(NotificationResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function unreadCount(Request $request, NotificationOperations $notifications): JsonResponse
    {
        return ApiResponse::data(['count' => $notifications->unreadCount($request->user())]);
    }

    public function read(NotificationMutationRequest $request, string $notification, NotificationOperations $notifications): JsonResponse
    {
        return ApiResponse::data(new NotificationResource($notifications->markRead($request->user(), $notification)));
    }

    public function readAll(NotificationMutationRequest $request, NotificationOperations $notifications): JsonResponse
    {
        return ApiResponse::data(['marked_read' => $notifications->markAllRead($request->user())]);
    }
}
