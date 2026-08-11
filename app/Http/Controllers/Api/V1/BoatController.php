<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fisher\CreateBoat;
use App\Actions\Fisher\DeleteBoat;
use App\Actions\Fisher\UpdateBoat;
use App\Http\Controllers\Controller;
use App\Http\Requests\Fisher\DeleteBoatRequest;
use App\Http\Requests\Fisher\FisherDirectoryRequest;
use App\Http\Requests\Fisher\StoreBoatRequest;
use App\Http\Requests\Fisher\UpdateBoatRequest;
use App\Http\Resources\Fisher\BoatResource;
use App\Models\Boat;
use App\Services\Fisher\FisherOperationsQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class BoatController extends Controller
{
    public function index(FisherDirectoryRequest $request, FisherOperationsQuery $operations): JsonResponse
    {
        $this->authorize('viewAny', Boat::class);
        $filters = $request->validated();
        $page = $operations->boats($request->user(), (int) ($filters['per_page'] ?? 20), $filters);
        $page->setCollection(BoatResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function store(StoreBoatRequest $request, CreateBoat $action): JsonResponse
    {
        return ApiResponse::data(new BoatResource($action->execute($request->user(), $request->validated())), 201);
    }

    public function show(Boat $boat): JsonResponse
    {
        $this->authorize('view', $boat);

        return ApiResponse::data(new BoatResource($boat));
    }

    public function update(UpdateBoatRequest $request, Boat $boat, UpdateBoat $action): JsonResponse
    {
        return ApiResponse::data(new BoatResource($action->execute($boat, $request->validated())));
    }

    public function destroy(DeleteBoatRequest $request, Boat $boat, DeleteBoat $action): Response
    {
        $action->execute($boat);

        return response()->noContent();
    }
}
