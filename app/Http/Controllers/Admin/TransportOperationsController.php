<?php

namespace App\Http\Controllers\Admin;

use App\Actions\IoT\ManageIotDevice;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TransportFilterRequest;
use App\Models\ColdChainAlert;
use App\Models\IotDevice;
use App\Models\SensorReading;
use App\Models\TransportTrip;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\Admin\AdminDirectoryPaginator;
use App\Services\Admin\AdminFilterOptions;
use App\Services\Admin\TransportOperationsExport;
use App\Services\Admin\TransportOperationsQuery;
use App\Services\Admin\TransportOperationsView;
use App\Services\Firebase\DeviceProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TransportOperationsController extends Controller
{
    public function __construct(
        private readonly AdminDirectoryPaginator $paginator,
        private readonly AdminFilterOptions $options,
    ) {}

    public function transporters(TransportFilterRequest $request, TransportOperationsQuery $query): View
    {
        $this->authorize('viewAny', User::class);
        $f = $request->validated();

        return view('admin.transport.transporters.index', ['transporters' => $this->paginator->paginate($query->transporters($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('TRANSPORTER'), 'filters' => $f]);
    }

    public function transporter(Request $request, User $transporter, TransportOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $transporter);

        return view('admin.transport.transporters.show', $view->transporter($transporter));
    }

    public function vehicles(TransportFilterRequest $request, TransportOperationsQuery $query): View
    {
        $this->authorize('viewAny', Vehicle::class);
        $f = $request->validated();

        return view('admin.transport.vehicles.index', ['vehicles' => $this->paginator->paginate($query->vehicles($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('TRANSPORTER'), 'filters' => $f]);
    }

    public function vehicle(Request $request, Vehicle $vehicle, TransportOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $vehicle);

        return view('admin.transport.vehicles.show', $view->vehicle($vehicle));
    }

    public function trips(TransportFilterRequest $request, TransportOperationsQuery $query): View
    {
        $this->authorize('viewAny', TransportTrip::class);
        $f = $request->validated();

        return view('admin.transport.trips.index', ['trips' => $this->paginator->paginate($query->trips($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('TRANSPORTER'), 'vehicles' => $this->options->vehicles(), 'filters' => $f]);
    }

    public function liveMap(Request $request, TransportOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('viewAny', TransportTrip::class);

        return view('admin.transport.map', $view->liveMap());
    }

    public function trip(Request $request, TransportTrip $trip, TransportOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $trip);

        return view('admin.transport.trips.show', $view->trip($trip));
    }

    public function devices(TransportFilterRequest $request, TransportOperationsQuery $query): View
    {
        $this->authorize('viewAny', IotDevice::class);
        $f = $request->validated();

        return view('admin.transport.devices.index', ['devices' => $this->paginator->paginate($query->devices($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('TRANSPORTER'), 'filters' => $f]);
    }

    public function createDevice(Request $request): View
    {
        $this->admin($request);
        $this->authorize('create', IotDevice::class);

        return view('admin.transport.devices.create', ['organizations' => $this->options->organizations('TRANSPORTER', true)]);
    }

    public function storeDevice(Request $request, ManageIotDevice $devices, DeviceProvisioner $provisioner): View|RedirectResponse
    {
        $this->admin($request);
        $this->authorize('create', IotDevice::class);
        $data = $request->validate([
            'organization_id' => ['required', 'uuid', Rule::exists('organizations', 'id')->where(fn ($query) => $query->where('type', 'TRANSPORTER')->where('is_active', true))],
            'device_code' => ['required', 'string', 'max:50', 'unique:iot_devices'],
            'serial_number' => ['required', 'string', 'max:100', 'unique:iot_devices'],
            'display_name' => ['required', 'string', 'max:120'],
            'firmware_version' => ['nullable', 'string', 'max:50'],
            'supports_product_temperature' => ['required', 'boolean'],
            'supports_air_temperature' => ['required', 'boolean'],
            'supports_humidity' => ['required', 'boolean'],
            'supports_gps' => ['required', 'boolean'],
            'supports_door_sensor' => ['required', 'boolean'],
            'provision_now' => ['required', 'boolean'],
        ]);
        $provisionNow = (bool) $data['provision_now'];
        unset($data['provision_now']);
        $device = $devices->create($request->user(), $data);

        if (! $provisionNow) {
            return redirect()->route('admin.transport.devices.show', $device)->with('status', 'IoT device created. Firebase access has not been provisioned.');
        }

        $credentials = $provisioner->provision($device, $request->user());

        return view('admin.transport.devices.credentials', compact('device', 'credentials'));
    }

    public function provisionDevice(Request $request, IotDevice $device, DeviceProvisioner $provisioner): View
    {
        $this->admin($request);
        $this->authorize('update', $device);
        $credentials = $provisioner->provision($device, $request->user());

        return view('admin.transport.devices.credentials', compact('device', 'credentials'));
    }

    public function device(Request $request, IotDevice $device, TransportOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $device);

        return view('admin.transport.devices.show', $view->device($device));
    }

    public function telemetry(TransportFilterRequest $request, TransportOperationsQuery $query): View
    {
        $this->authorize('viewAny', SensorReading::class);
        $f = $request->validated();

        return view('admin.transport.telemetry.index', ['readings' => $this->paginator->paginate($query->readings($f), (int) ($f['per_page'] ?? 50)), 'organizations' => $this->options->organizations('TRANSPORTER'), 'devices' => $this->options->devices(), 'trips' => $this->options->transportTrips(), 'filters' => $f]);
    }

    public function alerts(TransportFilterRequest $request, TransportOperationsQuery $query): View
    {
        $this->authorize('viewAny', ColdChainAlert::class);
        $f = $request->validated();

        return view('admin.transport.alerts.index', ['alerts' => $this->paginator->paginate($query->alerts($f), (int) ($f['per_page'] ?? 25)), 'organizations' => $this->options->organizations('TRANSPORTER'), 'types' => $this->options->alertTypes(), 'filters' => $f]);
    }

    public function alert(Request $request, ColdChainAlert $alert, TransportOperationsView $view): View
    {
        $this->admin($request);
        $this->authorize('view', $alert);

        return view('admin.transport.alerts.show', $view->alert($alert));
    }

    public function sync(TransportFilterRequest $request, TransportOperationsQuery $query): View
    {
        $this->admin($request);
        $this->authorize('viewAny', IotDevice::class);
        $f = $request->validated();

        return view('admin.transport.sync.index', ['devices' => $this->paginator->paginate($query->devices($f), 25, 'devices_page'), 'failures' => $this->paginator->paginate($query->syncFailures($f), 50, 'failures_page'), 'filters' => $f]);
    }

    public function exportTelemetry(TransportFilterRequest $request, TransportOperationsExport $export): StreamedResponse
    {
        $this->authorize('viewAny', SensorReading::class);

        return $export->telemetry($request->user(), $request->safe()->except('per_page'));
    }

    public function exportAlerts(TransportFilterRequest $request, TransportOperationsExport $export): StreamedResponse
    {
        $this->authorize('viewAny', ColdChainAlert::class);

        return $export->alerts($request->user(), $request->safe()->except('per_page'));
    }

    private function admin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('ADMIN'), 403);
    }
}
