<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\IoT\ManageIotDevice;
use App\Http\Controllers\Controller;
use App\Http\Requests\IoT\DeviceTelemetryFilterRequest;
use App\Http\Requests\IoT\IotDeviceDirectoryRequest;
use App\Http\Requests\IoT\MutateIotDeviceRequest;
use App\Http\Requests\IoT\StoreIotDeviceRequest;
use App\Http\Requests\IoT\UpdateIotDeviceRequest;
use App\Models\IotDevice;
use App\Services\Firebase\DeviceProvisioner;
use App\Services\IoT\DeviceTelemetryQuery;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class IotDeviceController extends Controller
{
    public function index(IotDeviceDirectoryRequest $request, DeviceTelemetryQuery $telemetry): JsonResponse
    {
        $filters = $request->validated();

        return ApiResponse::data($telemetry->devices($request->user(), (int) ($filters['per_page'] ?? 20)));
    }

    public function store(StoreIotDeviceRequest $request, ManageIotDevice $action): JsonResponse
    {
        return ApiResponse::data($action->create($request->user(), $request->validated()), 201);
    }

    public function show(IotDevice $device, DeviceTelemetryQuery $telemetry): JsonResponse
    {
        $this->authorize('view', $device);

        return ApiResponse::data($telemetry->details($device));
    }

    public function update(UpdateIotDeviceRequest $request, IotDevice $device, ManageIotDevice $action): JsonResponse
    {
        return ApiResponse::data($action->update($request->user(), $device, $request->validated()));
    }

    public function provision(MutateIotDeviceRequest $request, IotDevice $device, DeviceProvisioner $provisioner): JsonResponse
    {
        return ApiResponse::data($provisioner->provision($device, $request->user()));
    }

    public function rotate(MutateIotDeviceRequest $request, IotDevice $device, DeviceProvisioner $provisioner): JsonResponse
    {
        return ApiResponse::data($provisioner->provision($device, $request->user(), true));
    }

    public function disableFirebase(MutateIotDeviceRequest $request, IotDevice $device, DeviceProvisioner $provisioner): JsonResponse
    {
        return ApiResponse::data($provisioner->disableFirebase($device, $request->user()));
    }

    public function activate(MutateIotDeviceRequest $request, IotDevice $device, DeviceProvisioner $provisioner): JsonResponse
    {
        return ApiResponse::data($provisioner->activate($device, $request->user()));
    }

    public function deactivate(MutateIotDeviceRequest $request, IotDevice $device, DeviceProvisioner $provisioner): JsonResponse
    {
        return ApiResponse::data($provisioner->deactivate($device, $request->user()));
    }

    public function health(IotDevice $device, DeviceTelemetryQuery $telemetry): JsonResponse
    {
        $this->authorize('view', $device);

        return ApiResponse::data($telemetry->health($device));
    }

    public function readings(DeviceTelemetryFilterRequest $request, IotDevice $device, DeviceTelemetryQuery $telemetry): JsonResponse
    {
        return ApiResponse::data($telemetry->readings($device, $request->validated()));
    }

    public function latestReading(IotDevice $device, DeviceTelemetryQuery $telemetry): JsonResponse
    {
        $this->authorize('view', $device);

        return ApiResponse::data($telemetry->latestReading($device));
    }

    public function syncStatus(IotDevice $device, DeviceTelemetryQuery $telemetry): JsonResponse
    {
        $this->authorize('view', $device);

        return ApiResponse::data($telemetry->syncStatus($device));
    }
}
