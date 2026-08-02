@php
    $config = match ($kind) {
        'species' => ['Fish species', 'Maintain approved species names used by catches and batches.', 'admin.reference-data.species.create', 'admin.reference-data.species.edit', 'Common or scientific name', ['common_name' => 'Common name', 'scientific_name' => 'Scientific name', 'created_at' => 'Created date'], 'fish species'],
        'gear' => ['Fishing gear', 'Maintain approved fishing methods used during catch registration.', 'admin.reference-data.gear.create', 'admin.reference-data.gear.edit', 'Gear name', ['name' => 'Name', 'created_at' => 'Created date'], 'gear type'],
        default => ['Landing sites', 'Maintain recognized landing locations for fishing trips.', 'admin.reference-data.sites.create', 'admin.reference-data.sites.edit', 'Site or district', ['name' => 'Name', 'district' => 'District', 'created_at' => 'Created date'], 'landing site'],
    };
@endphp
<x-layouts.admin :title="$config[0].' | FishTrace'">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="text-sm font-medium text-cyan-700">Fishing reference data</p><h1 class="text-3xl font-bold">{{ $config[0] }}</h1><p class="mt-1 text-slate-500">{{ $config[1] }}</p></div>
        <a href="{{ route($config[2]) }}" class="rounded-lg bg-[#075e63] px-4 py-2 font-semibold text-white">Add {{ $config[6] }}</a>
    </header>
    @include('admin.reference-data._nav')
    <form class="mt-5 grid gap-3 rounded-xl bg-white p-4 shadow-sm md:grid-cols-5">
        <label class="md:col-span-2"><span class="sr-only">Search</span><input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ $config[4] }}" class="w-full rounded-lg border-slate-300"></label>
        <label><span class="sr-only">Status</span><select name="is_active" class="w-full rounded-lg border-slate-300"><option value="">All statuses</option><option value="1" @selected((string)($filters['is_active'] ?? '') === '1')>Active</option><option value="0" @selected((string)($filters['is_active'] ?? '') === '0')>Inactive</option></select></label>
        <label><span class="sr-only">Sort by</span><select name="sort" class="w-full rounded-lg border-slate-300">@foreach($config[5] as $value => $label)<option value="{{ $value }}" @selected(($filters['sort'] ?? '') === $value)>Sort: {{ $label }}</option>@endforeach</select></label>
        <div class="flex gap-2"><input type="hidden" name="direction" value="{{ ($filters['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc' }}"><button class="flex-1 rounded-lg bg-slate-800 px-4 py-2 text-white">Filter</button><a href="{{ url()->current() }}" class="rounded-lg border border-slate-300 px-4 py-2 text-slate-700">Clear</a></div>
    </form>
    <section class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm" aria-labelledby="reference-results">
        <h2 id="reference-results" class="sr-only">{{ $config[0] }} results</h2>
        <table class="w-full text-left text-sm"><thead class="border-b bg-slate-50 text-slate-500"><tr><th class="p-4">Name</th>@if($kind !== 'gear')<th>{{ $kind === 'species' ? 'Scientific name' : 'District' }}</th>@endif<th>Usage</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>@forelse($records as $record)<tr class="border-b border-slate-100"><td class="p-4 font-semibold text-slate-900">{{ $kind === 'species' ? $record->common_name : $record->name }}</td>@if($kind !== 'gear')<td class="text-slate-600">{{ ($kind === 'species' ? $record->scientific_name : $record->district) ?: '—' }}</td>@endif<td>{{ $kind === 'species' ? ($record->catches_count.' catches · '.$record->batches_count.' batches') : ($kind === 'gear' ? $record->catches_count.' catches' : $record->trips_count.' trips') }}</td><td><x-admin.status :value="$record->is_active ? 'ACTIVE' : 'INACTIVE'" /></td><td class="pr-4 text-right"><a href="{{ route($config[3], $record) }}" class="font-semibold text-cyan-700">Edit</a></td></tr>@empty<tr><td colspan="5" class="p-10 text-center text-slate-500">No records match these filters.</td></tr>@endforelse</tbody>
        </table>
        <div class="p-4">{{ $records->links() }}</div>
    </section>
</x-layouts.admin>
