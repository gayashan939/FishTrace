<nav class="mb-6 flex flex-wrap gap-2" aria-label="Transport operations">
    @foreach ([['Transporters','admin.transport.transporters.index','admin.transport.transporters.*'],['Vehicles','admin.transport.vehicles.index','admin.transport.vehicles.*'],['Trips','admin.transport.trips.index','admin.transport.trips.*'],['Devices','admin.transport.devices.index','admin.transport.devices.*'],['Telemetry','admin.transport.telemetry.index','admin.transport.telemetry.*'],['Alerts','admin.transport.alerts.index','admin.transport.alerts.*'],['Sync health','admin.transport.sync.index','admin.transport.sync.*']] as [$label,$route,$pattern])
        <a href="{{ route($route) }}" class="rounded-lg border px-3 py-2 text-sm font-medium {{ request()->routeIs($pattern) ? 'border-cyan-700 bg-cyan-700 text-white' : 'border-slate-300 bg-white' }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
@if(request()->routeIs('admin.transport.devices.*') && !request()->routeIs('admin.transport.devices.create'))
    <div class="mb-5 flex justify-end gap-3">
        @if(request()->routeIs('admin.transport.devices.show') && isset($device) && !$device->firebase_auth_enabled)
            <form method="post" action="{{ route('admin.transport.devices.provision', $device) }}">@csrf<button class="rounded-lg border border-cyan-700 px-4 py-2 font-semibold text-cyan-800" onclick="return confirm('Generate one-time Firebase credentials for this device?')">Provision Firebase</button></form>
        @endif
        <a href="{{ route('admin.transport.devices.create') }}" class="rounded-lg bg-[#075e63] px-4 py-2 font-semibold text-white">Add IoT device</a>
    </div>
@endif
