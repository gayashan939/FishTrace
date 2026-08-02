<nav class="mt-6 flex flex-wrap gap-2" aria-label="Fishing reference data">
    <a href="{{ route('admin.reference-data.species.index') }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $kind === 'species' ? 'bg-[#075e63] text-white' : 'bg-white text-slate-700' }}" @if($kind === 'species') aria-current="page" @endif>Fish species</a>
    <a href="{{ route('admin.reference-data.gear.index') }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $kind === 'gear' ? 'bg-[#075e63] text-white' : 'bg-white text-slate-700' }}" @if($kind === 'gear') aria-current="page" @endif>Fishing gear</a>
    <a href="{{ route('admin.reference-data.sites.index') }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ $kind === 'sites' ? 'bg-[#075e63] text-white' : 'bg-white text-slate-700' }}" @if($kind === 'sites') aria-current="page" @endif>Landing sites</a>
</nav>
