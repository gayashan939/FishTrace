<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fisher\CreateFishingTrip;
use App\Actions\Fisher\TransitionFishingTrip;
use App\Actions\Fisher\UpdateFishingTrip;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fisher\FisherDirectoryRequest;
use App\Http\Requests\Fisher\MutateFishingTripRequest;
use App\Http\Requests\Fisher\StoreFishingTripRequest;
use App\Http\Requests\Fisher\UpdateFishingTripRequest;
use App\Http\Resources\Fisher\FishingTripResource;
use App\Models\FishingTrip;
use App\Services\Fisher\FisherDashboard;
use App\Services\Fisher\FisherOperationsQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FishingTripController extends Controller
{
    public function dashboard(Request $request, FisherDashboard $dashboard): JsonResponse
    {
        $this->authorize('viewAny', FishingTrip::class);

        return ApiResponse::data($dashboard->get($request->user()));
    }

    public function index(FisherDirectoryRequest $request, FisherOperationsQuery $operations): JsonResponse
    {
        $this->authorize('viewAny', FishingTrip::class);
        $filters = $request->validated();
        $page = $operations->trips($request->user(), (int) ($filters['per_page'] ?? 20));
        $page->setCollection(FishingTripResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function store(StoreFishingTripRequest $request, CreateFishingTrip $action): JsonResponse
    {
        return ApiResponse::data(new FishingTripResource($action->execute($request->user(), $request->validated())), 201);
    }

    public function show(FishingTrip $fishingTrip, FisherOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $fishingTrip);

        return ApiResponse::data(new FishingTripResource($operations->trip($fishingTrip)));
    }

    public function update(UpdateFishingTripRequest $request, FishingTrip $fishingTrip, UpdateFishingTrip $action): JsonResponse
    {
        return ApiResponse::data(new FishingTripResource($action->execute($request->user(), $fishingTrip, $request->validated())));
    }

    public function start(MutateFishingTripRequest $request, FishingTrip $fishingTrip, TransitionFishingTrip $action): JsonResponse
    {
        return ApiResponse::data(new FishingTripResource($action->start($request->user(), $fishingTrip)));
    }

    public function complete(MutateFishingTripRequest $request, FishingTrip $fishingTrip, TransitionFishingTrip $action): JsonResponse
    {
        return ApiResponse::data(new FishingTripResource($action->complete($request->user(), $fishingTrip)));
    }

    public function cancel(MutateFishingTripRequest $request, FishingTrip $fishingTrip, TransitionFishingTrip $action): JsonResponse
    {
        return ApiResponse::data(new FishingTripResource($action->cancel($request->user(), $fishingTrip)));
    }
}
