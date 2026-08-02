@php
    $editing = $record->exists;
    $config = match ($kind) {
        'species' => ['Fish species', 'admin.reference-data.species.index', 'admin.reference-data.species.store', 'admin.reference-data.species.update'],
        'gear' => ['Fishing gear type', 'admin.reference-data.gear.index', 'admin.reference-data.gear.store', 'admin.reference-data.gear.update'],
        default => ['Landing site', 'admin.reference-data.sites.index', 'admin.reference-data.sites.store', 'admin.reference-data.sites.update'],
    };
@endphp
<x-layouts.admin :title="($editing ? 'Edit ' : 'Add ').$config[0].' | FishTrace'">
    <header><p class="text-sm font-medium text-cyan-700">Fishing reference data</p><h1 class="text-3xl font-bold">{{ $editing ? 'Edit' : 'Add' }} {{ strtolower($config[0]) }}</h1><p class="mt-1 text-slate-500">Inactive records remain attached to historical operations but cannot be selected for new work.</p></header>
    @include('admin.reference-data._nav')
    <form method="post" action="{{ $editing ? route($config[3], $record) : route($config[2]) }}" class="mt-6 max-w-2xl rounded-xl bg-white p-6 shadow-sm">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="grid gap-5 md:grid-cols-2">
            @if($kind === 'species')
                <label><span class="text-sm font-medium">Common name</span><input name="common_name" required maxlength="160" value="{{ old('common_name', $record->common_name) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
                <label><span class="text-sm font-medium">Scientific name</span><input name="scientific_name" maxlength="160" value="{{ old('scientific_name', $record->scientific_name) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
            @elseif($kind === 'gear')
                <label class="md:col-span-2"><span class="text-sm font-medium">Gear name</span><input name="name" required maxlength="160" value="{{ old('name', $record->name) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
            @else
                <label><span class="text-sm font-medium">Site name</span><input name="name" required maxlength="160" value="{{ old('name', $record->name) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
                <label><span class="text-sm font-medium">District</span><input name="district" maxlength="120" value="{{ old('district', $record->district) }}" class="mt-1 w-full rounded-lg border-slate-300"></label>
            @endif
        </div>
        <input type="hidden" name="is_active" value="0">
        <label class="mt-5 flex items-center gap-3"><input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-cyan-700" @checked((bool) old('is_active', $record->exists ? $record->is_active : true))><span><span class="block text-sm font-medium">Active</span><span class="block text-xs text-slate-500">Available for new operational records.</span></span></label>
        <div class="mt-7 flex flex-wrap gap-3"><button class="rounded-lg bg-[#075e63] px-5 py-2.5 font-semibold text-white">{{ $editing ? 'Save changes' : 'Create record' }}</button><a href="{{ route($config[1]) }}" class="rounded-lg border border-slate-300 px-5 py-2.5 font-semibold text-slate-700">Cancel</a></div>
    </form>
</x-layouts.admin>
