<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Transport\AddBatchToTrip;
use App\Actions\Transport\AssignDeviceToTrip;
use App\Actions\Transport\CancelTransportTrip;
use App\Actions\Transport\CompleteTransportTrip;
use App\Actions\Transport\ConfirmTransportDelivery;
use App\Actions\Transport\CreateTransportTrip;
use App\Actions\Transport\MarkTransportTripArrived;
use App\Actions\Transport\RecordTransportIncident;
use App\Actions\Transport\RemoveBatchFromTrip;
use App\Actions\Transport\RemoveDeviceFromTrip;
use App\Actions\Transport\StartTransportTrip;
use App\Actions\Transport\UpdateTransportTrip;
use App\Http\Controllers\Controller;
use App\Http\Requests\IoT\SensorSummaryRequest;
use App\Http\Requests\IoT\TransportTelemetryFilterRequest;
use App\Http\Requests\Transport\AddTransportBatchRequest;
use App\Http\Requests\Transport\AssignDeviceRequest;
use App\Http\Requests\Transport\CancelTransportTripRequest;
use App\Http\Requests\Transport\CompleteTransportTripRequest;
use App\Http\Requests\Transport\MutateTransportTripRequest;
use App\Http\Requests\Transport\ResolveTransportBatchRequest;
use App\Http\Requests\Transport\StoreDeliveryConfirmationRequest;
use App\Http\Requests\Transport\StoreTransportIncidentRequest;
use App\Http\Requests\Transport\StoreTransportTripRequest;
use App\Http\Requests\Transport\TransportTripDirectoryRequest;
use App\Http\Requests\Transport\UpdateTransportChecklistRequest;
use App\Http\Requests\Transport\UpdateTransportTripRequest;
use App\Http\Resources\Fisher\FishBatchResource;
use App\Http\Resources\IoT\SensorReadingResource;
use App\Http\Resources\Transport\DeliveryConfirmationResource;
use App\Http\Resources\Transport\DeviceAssignmentResource;
use App\Http\Resources\Transport\PreTripChecklistResource;
use App\Http\Resources\Transport\TransportIncidentResource;
use App\Http\Resources\Transport\TransportTripResource;
use App\Models\FishBatch;
use App\Models\TransportTrip;
use App\Services\Transport\TransportChecklistService;
use App\Services\Transport\TransportOperationsQuery;
use App\Services\Transport\TransportOperationsSummary;
use App\Services\Transport\TransportTelemetryView;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransportTripController extends Controller
{
    public function availableBatches(TransportTripDirectoryRequest $request, TransportOperationsQuery $operations): JsonResponse
    {
        $page = $operations->availableBatches((int) ($request->validated('per_page') ?? 25));
        $page->setCollection(FishBatchResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function resolveBatch(ResolveTransportBatchRequest $request, TransportOperationsQuery $operations): JsonResponse
    {
        $batch = $operations->resolveAvailableBatch((string) $request->validated('code'));
        abort_if($batch === null, 404, 'No transport-ready batch matches this QR code.');

        return ApiResponse::data(new FishBatchResource($batch));
    }

    public function dashboard(Request $request, TransportOperationsSummary $summary, TransportTelemetryView $view): JsonResponse
    {
        $this->authorize('viewAny', TransportTrip::class);
        $organization = $request->user()->primaryOrganization();
        abort_unless($organization !== null, 403);

        return ApiResponse::data($view->dashboard($summary->dashboard($organization->id), $request));
    }

    public function index(TransportTripDirectoryRequest $request, TransportOperationsQuery $operations): JsonResponse
    {
        $filters = $request->validated();
        $page = $operations->trips($request->user(), (int) ($filters['per_page'] ?? 20));
        $page->setCollection(TransportTripResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function store(StoreTransportTripRequest $request, CreateTransportTrip $action): JsonResponse
    {
        return ApiResponse::data(new TransportTripResource($action->execute($request->user(), $request->validated())), 201);
    }

    public function show(TransportTrip $transportTrip, TransportOperationsQuery $operations): JsonResponse
    {
        $this->authorize('view', $transportTrip);

        return ApiResponse::data(new TransportTripResource($operations->trip($transportTrip)));
    }

    public function update(UpdateTransportTripRequest $request, TransportTrip $transportTrip, UpdateTransportTrip $action): JsonResponse
    {
        return ApiResponse::data(new TransportTripResource($action->execute($request->user(), $transportTrip, $request->validated())));
    }

    public function checklist(TransportTrip $transportTrip, TransportChecklistService $checklists): JsonResponse
    {
        $this->authorize('view', $transportTrip);

        return ApiResponse::data(new PreTripChecklistResource($checklists->get($transportTrip)));
    }

    public function updateChecklist(UpdateTransportChecklistRequest $request, TransportTrip $transportTrip, TransportChecklistService $checklists): JsonResponse
    {
        return ApiResponse::data(new PreTripChecklistResource($checklists->update($request->user(), $transportTrip, $request->itemStates())));
    }

    public function addBatch(AddTransportBatchRequest $request, TransportTrip $transportTrip, AddBatchToTrip $action): JsonResponse
    {
        return ApiResponse::data(new TransportTripResource($action->execute($request->user(), $transportTrip, $request->string('batch_id')->toString())));
    }

    public function removeBatch(MutateTransportTripRequest $request, TransportTrip $transportTrip, FishBatch $batch, RemoveBatchFromTrip $action): JsonResponse
    {
        return ApiResponse::data(new TransportTripResource($action->execute($request->user(), $transportTrip, $batch)));
    }

    public function assignDevice(AssignDeviceRequest $request, TransportTrip $transportTrip, AssignDeviceToTrip $action): JsonResponse
    {
        $this->authorize('update', $transportTrip);

        return ApiResponse::data(new DeviceAssignmentResource($action->execute($transportTrip, $request->validated('iot_device_id'), $request->validated('expires_at'))));
    }

    public function removeDevice(MutateTransportTripRequest $request, TransportTrip $transportTrip, RemoveDeviceFromTrip $action): JsonResponse
    {
        return ApiResponse::data(new DeviceAssignmentResource($action->execute($transportTrip)));
    }

    public function start(MutateTransportTripRequest $request, TransportTrip $transportTrip, StartTransportTrip $action): JsonResponse
    {
        return ApiResponse::data(new TransportTripResource($action->execute($request->user(), $transportTrip)));
    }

    public function complete(CompleteTransportTripRequest $request, TransportTrip $transportTrip, CompleteTransportTrip $action): JsonResponse
    {
        return ApiResponse::data(new TransportTripResource($action->execute($request->user(), $transportTrip)));
    }

    public function arrive(MutateTransportTripRequest $request, TransportTrip $transportTrip, MarkTransportTripArrived $action): JsonResponse
    {
        return ApiResponse::data(new TransportTripResource($action->execute($request->user(), $transportTrip)));
    }

    public function cancel(CancelTransportTripRequest $request, TransportTrip $transportTrip, CancelTransportTrip $action): JsonResponse
    {
        return ApiResponse::data(new TransportTripResource($action->execute($request->user(), $transportTrip, $request->string('reason')->toString())));
    }

    public function incident(StoreTransportIncidentRequest $request, TransportTrip $transportTrip, RecordTransportIncident $action): JsonResponse
    {
        return ApiResponse::data(new TransportIncidentResource($action->execute($request->user(), $transportTrip, $request->validated())), 201);
    }

    public function confirmDelivery(StoreDeliveryConfirmationRequest $request, TransportTrip $transportTrip, ConfirmTransportDelivery $action): JsonResponse
    {
        return ApiResponse::data(new DeliveryConfirmationResource($action->execute($request->user(), $transportTrip, $request->validated())));
    }

    public function readings(TransportTelemetryFilterRequest $request, TransportTrip $transportTrip, TransportOperationsQuery $operations): JsonResponse
    {
        $page = $operations->readings($transportTrip, $request->validated());
        $page->setCollection(SensorReadingResource::collection($page->getCollection())->collection);

        return ApiResponse::data($page);
    }

    public function latest(Request $request, TransportTrip $transportTrip, TransportOperationsQuery $operations, TransportTelemetryView $view): JsonResponse
    {
        $this->authorize('view', $transportTrip);

        return ApiResponse::data($view->latest($operations->latestReading($transportTrip), $operations->activeAlertCount($transportTrip), $request));
    }

    public function sensorSummary(SensorSummaryRequest $request, TransportTrip $transportTrip, TransportOperationsSummary $summary, TransportTelemetryView $view): JsonResponse
    {
        return ApiResponse::data($view->summary($summary->sensorSummary($transportTrip, (string) $request->validated('period', 'day')), $request));
    }

    public function liveAccess(Request $request, TransportTrip $transportTrip, TransportOperationsQuery $operations, TransportTelemetryView $view): JsonResponse
    {
        $this->authorize('view', $transportTrip);

        return ApiResponse::data($view->liveAccess($operations->liveAccess($transportTrip), $request));
    }
}
