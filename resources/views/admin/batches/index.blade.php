<x-layouts.admin title="Batches | FishTrace">
    <header class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-sm font-medium text-cyan-700">Traceability operations</p>
            <h1 class="text-3xl font-bold">Batch registry</h1>
            <p class="mt-1 text-slate-500">Search every batch and follow its supply-chain state.</p>
        </div>
        <a href="{{ route('admin.batches.export', request()->query()) }}" class="rounded-lg border border-cyan-700 px-4 py-2 font-semibold text-cyan-800">Export CSV</a>
    </header>
    <form class="mt-7 rounded-xl bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-3 xl:grid-cols-5">
            <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Batch code or product" class="rounded-lg border-slate-300">
            <select name="status" class="rounded-lg border-slate-300">
                <option value="">All statuses</option>
                @foreach (App\Enums\BatchStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ str_replace('_', ' ', $status->value) }}</option>
                @endforeach
            </select>
            <select name="organization_id" class="rounded-lg border-slate-300">
                <option value="">All organizations</option>
                @foreach ($organizations as $organization)
                    <option value="{{ $organization->id }}" @selected(($filters['organization_id'] ?? '') === $organization->id)>{{ $organization->name }}</option>
                @endforeach
            </select>
            <select name="species_id" class="rounded-lg border-slate-300">
                <option value="">All species</option>
                @foreach ($species as $fishSpecies)
                    <option value="{{ $fishSpecies->id }}" @selected(($filters['species_id'] ?? '') === $fishSpecies->id)>{{ $fishSpecies->common_name }}</option>
                @endforeach
            </select>
            <select name="type" class="rounded-lg border-slate-300">
                <option value="">All batch types</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ $type }}</option>
                @endforeach
            </select>
            <select name="is_recalled" class="rounded-lg border-slate-300">
                <option value="">Recall: any</option>
                <option value="1" @selected(($filters['is_recalled'] ?? null) === '1')>Recalled only</option>
                <option value="0" @selected(($filters['is_recalled'] ?? null) === '0')>Not recalled</option>
            </select>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="rounded-lg border-slate-300" aria-label="Created from">
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="rounded-lg border-slate-300" aria-label="Created to">
            <select name="sort" class="rounded-lg border-slate-300">
                <option value="created_at">Newest created</option>
                <option value="batch_code" @selected(($filters['sort'] ?? '') === 'batch_code')>Batch code</option>
                <option value="status" @selected(($filters['sort'] ?? '') === 'status')>Status</option>
                <option value="total_weight_kg" @selected(($filters['sort'] ?? '') === 'total_weight_kg')>Weight</option>
            </select>
            <div class="flex gap-2"><button class="flex-1 rounded-lg bg-slate-800 px-4 py-2 text-white">Filter</button><a href="{{ route('admin.batches.index') }}" class="rounded-lg border border-slate-300 px-4 py-2">Clear</a></div>
        </div>
    </form>
    <section class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-slate-500"><tr><th class="p-4">Batch</th><th>Organization</th><th>Status</th><th>Weight</th><th>Trace links</th><th>AI risk</th></tr></thead>
                <tbody>
                @forelse ($batches as $batch)
                    <tr class="border-b border-slate-100">
                        <td class="p-4"><a href="{{ route('admin.batches.show', $batch) }}" class="font-semibold text-[#075e63]">{{ $batch->batch_code }}</a><div class="text-slate-500">{{ $batch->species?->common_name }} · {{ $batch->product_type }}</div></td>
                        <td>{{ $batch->organization?->name }}<div class="text-xs text-slate-500">{{ $batch->type }}</div></td>
                        <td><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $batch->is_recalled ? 'bg-red-100 text-red-800' : 'bg-cyan-50 text-cyan-800' }}">{{ str_replace('_', ' ', $batch->status->value) }}</span></td>
                        <td>{{ number_format((float) $batch->total_weight_kg, 3) }} kg</td>
                        <td>{{ $batch->events_count }} events · {{ $batch->transport_trips_count }} trips · {{ $batch->child_links_count }} children</td>
                        <td>@if ($batch->latestAiPrediction)<span class="font-semibold {{ $batch->latestAiPrediction->risk_level === 'HIGH' ? 'text-red-700' : 'text-slate-700' }}">{{ $batch->latestAiPrediction->risk_level }}</span><div class="text-xs text-slate-500">{{ number_format($batch->latestAiPrediction->confidence * 100, 1) }}%</div>@else<span class="text-slate-400">Not assessed</span>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-12 text-center text-slate-500">No batches match these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $batches->links() }}</div>
    </section>
</x-layouts.admin>
