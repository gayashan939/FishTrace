<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Transport\ManageVehicle;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transport\DeleteVehicleRequest;
use App\Http\Requests\Transport\StoreVehicleRequest;
use App\Http\Requests\Transport\UpdateVehicleRequest;
use App\Http\Requests\Transport\VehicleDirectoryRequest;
use App\Http\Resources\Transport\VehicleResource;
use App\Models\Vehicle;
use App\Services\Transport\TransportOperationsQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class VehicleController extends Controller
{
    public function index(VehicleDirectoryRequest $request, TransportOperationsQuery $operations): JsonResponse
    {
        $filters = $request->validated();
        $page = $operations->vehicles($request->user(), (int) ($filters['per_page'] ?? 20));
        $page->setCollection(VehicleResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function store(StoreVehicleRequest $request, ManageVehicle $action): JsonResponse
    {
        return ApiResponse::data(new VehicleResource($action->create($request->user(), $request->validated())), 201);
    }

    public function show(Vehicle $vehicle, TransportOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $vehicle);

        return ApiResponse::data(new VehicleResource($operations->vehicle($vehicle)));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle, ManageVehicle $action): JsonResponse
    {
        return ApiResponse::data(new VehicleResource($action->update($request->user(), $vehicle, $request->validated())));
    }

    public function destroy(DeleteVehicleRequest $request, Vehicle $vehicle, ManageVehicle $action): Response
    {
        $action->delete($request->user(), $vehicle);

        return response()->noContent();
    }
}
