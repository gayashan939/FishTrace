<x-layouts.admin title="Live Transport Map | FishTrace">
    @include('admin.transport._nav')

    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-cyan-700">Authenticated operations view</p>
            <h1 class="text-3xl font-bold">Live transport map</h1>
            <p class="mt-1 text-slate-500">Exact GPS positions are available only to authorized administrators.</p>
        </div>
        <span class="rounded-full bg-cyan-50 px-4 py-2 text-sm font-semibold text-cyan-800">{{ count($liveTrips) }} active trips</span>
    </header>

    @if (collect($liveTrips)->contains(fn ($trip) => $trip['current_position'] !== null))
        <section class="mt-6 overflow-hidden rounded-2xl bg-white p-4 shadow-sm">
            <div class="h-[34rem] rounded-xl" data-live-transport-map data-destination-radius="{{ config('fishtrace.geofencing.destination_radius_meters') }}" data-trips='@json($liveTrips)'></div>
            <p class="mt-3 text-xs text-slate-500">Map data is operationally sensitive. Consumer trace pages never receive these coordinates.</p>
        </section>
    @else
        <section class="mt-6 rounded-2xl bg-white p-10 text-center shadow-sm">
            <h2 class="text-lg font-bold">No live GPS positions</h2>
            <p class="mt-2 text-slate-500">Active trips will appear after their assigned devices report GPS telemetry.</p>
        </section>
    @endif
</x-layouts.admin>
