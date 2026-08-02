@php
    $items = [
        ['Overview', 'admin.dashboard', 'admin.dashboard'],
        ['Users', 'admin.users.index', 'admin.users.*'],
        ['Organizations', 'admin.organizations.index', 'admin.organizations.*'],
        ['Roles', 'admin.roles.index', 'admin.roles.*'],
        ['Batches', 'admin.batches.index', 'admin.batches.*'],
        ['Fishing operations', 'admin.fishing.trips.index', 'admin.fishing.*'],
        ['Fishing reference data', 'admin.reference-data.species.index', 'admin.reference-data.*'],
        ['Transport & IoT', 'admin.transport.trips.index', 'admin.transport.*'],
        ['Processor operations', 'admin.processor.records.index', 'admin.processor.*'],
        ['Retail operations', 'admin.retail.inventory.index', 'admin.retail.*'],
        ['Compliance & reporting', 'admin.compliance.incidents.index', 'admin.compliance.*'],
        ['Sensor history', 'admin.transport.telemetry.index', 'admin.transport.telemetry.*'],
        ['Audit logs', 'admin.audit-logs', 'admin.audit-logs'],
        ['Settings', 'admin.settings.alert-rules.index', 'admin.settings.*'],
    ];
@endphp
<nav class="space-y-1" aria-label="Primary navigation">
    @foreach($items as [$label, $route, $pattern])
        @php($active = request()->routeIs($pattern) && ! ($route === 'admin.transport.trips.index' && request()->routeIs('admin.transport.telemetry.*')))
        <a class="block rounded-lg px-3 py-2 text-sm font-medium transition {{ $active ? 'bg-white text-[#073b3a]' : 'text-cyan-50 hover:bg-white/10 hover:text-white' }}" href="{{ route($route) }}" @if($active) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
