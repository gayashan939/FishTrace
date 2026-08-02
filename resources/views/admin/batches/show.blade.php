<x-layouts.admin title="{{ $batch->batch_code }} | FishTrace">
    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.batches.index') }}" class="text-sm text-cyan-700">← Batch registry</a>
            <h1 class="mt-2 text-3xl font-bold">{{ $batch->batch_code }}</h1>
            <p class="mt-1 text-slate-500">{{ $batch->species?->common_name }} · {{ $batch->species?->scientific_name }} · {{ $batch->product_type }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($batch->qrCode && ! $batch->qrCode->revoked_at)
                <a href="{{ route('admin.batches.qr', $batch) }}" class="rounded-lg border border-cyan-700 px-4 py-2 text-cyan-800">View QR</a>
            @endif
            @if ($batch->is_public && $batch->qrCode && ! $batch->qrCode->revoked_at)
                <span class="rounded-lg bg-emerald-100 px-4 py-2 text-sm font-semibold text-emerald-800">Public trace active</span>
            @endif
        </div>
    </header>

    @if ($batch->is_recalled)
        <div class="mt-6 rounded-xl border border-red-300 bg-red-50 p-4 font-semibold text-red-900">This batch is recalled. Downstream stock must remain blocked.</div>
    @endif

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Status</p><p class="mt-2 font-bold text-[#075e63]">{{ str_replace('_', ' ', $batch->status->value) }}</p></article>
        <article class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Weight</p><p class="mt-2 text-xl font-bold">{{ number_format((float) $batch->total_weight_kg, 3) }} kg</p></article>
        <article class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Owner</p><p class="mt-2 font-bold">{{ $batch->organization?->name }}</p><p class="text-xs text-slate-500">{{ $batch->organization?->code }}</p></article>
        <article class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">Created by</p><p class="mt-2 font-bold">{{ $batch->creator?->name }}</p><p class="text-xs text-slate-500">{{ $batch->created_at?->format('Y-m-d H:i') }}</p></article>
        <article class="rounded-xl bg-white p-5 shadow-sm"><p class="text-sm text-slate-500">AI risk</p>@if ($batch->latestAiPrediction)<p class="mt-2 text-xl font-bold">{{ $batch->latestAiPrediction->risk_level }}</p><p class="text-xs text-slate-500">{{ number_format($batch->latestAiPrediction->confidence * 100, 1) }}% confidence</p>@else<p class="mt-2 text-slate-400">Not assessed</p>@endif</article>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold">Catch origin</h2>
            <div class="mt-4 space-y-3">
                @forelse ($batch->catches as $catch)
                    <article class="rounded-lg border border-slate-200 p-4"><div class="flex justify-between gap-4"><div><p class="font-semibold">{{ $catch->trip?->trip_code ?? 'Catch record' }}</p><p class="text-sm text-slate-500">{{ $catch->trip?->general_catch_area }} · {{ $catch->trip?->boat?->name }} {{ $catch->trip?->boat?->registration_number }}</p></div><p class="font-semibold">{{ $catch->pivot->allocated_weight_kg }} kg</p></div><p class="mt-2 text-xs text-slate-500">Caught {{ $catch->caught_at?->format('Y-m-d H:i') }} · {{ $catch->quantity }} fish</p></article>
                @empty
                    <p class="text-slate-500">No direct catch records are linked to this derived batch.</p>
                @endforelse
            </div>
        </section>
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold">Parent and child batches</h2>
            <div class="mt-4 space-y-3">
                @foreach ($batch->parentLinks as $link)
                    <a href="{{ route('admin.batches.show', $link->parent) }}" class="flex justify-between rounded-lg border border-slate-200 p-4"><span><strong>Parent:</strong> {{ $link->parent->batch_code }}</span><span>{{ $link->allocated_weight_kg }} kg</span></a>
                @endforeach
                @foreach ($batch->childLinks as $link)
                    <a href="{{ route('admin.batches.show', $link->child) }}" class="flex justify-between rounded-lg border border-slate-200 p-4"><span><strong>Child:</strong> {{ $link->child->batch_code }}</span><span>{{ $link->allocated_weight_kg }} kg</span></a>
                @endforeach
                @if ($batch->parentLinks->isEmpty() && $batch->childLinks->isEmpty())
                    <p class="text-slate-500">No split relationships recorded.</p>
                @endif
            </div>
        </section>
    </div>

    <section class="mt-6 rounded-xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold">Traceability timeline</h2>
        <div class="mt-5 border-l-2 border-cyan-200 pl-5">
            @forelse ($batch->events as $event)
                <article class="relative mb-6"><span class="absolute -left-[27px] top-1 h-3 w-3 rounded-full bg-[#075e63]"></span><div class="flex flex-wrap justify-between gap-2"><div><p class="font-semibold">{{ $event->title }}</p><p class="text-xs font-medium text-cyan-700">{{ str_replace('_', ' ', $event->event_type) }}</p></div><time class="text-sm text-slate-500">{{ $event->occurred_at?->format('Y-m-d H:i:s') }}</time></div>@if ($event->public_data)<p class="mt-2 break-words text-sm text-slate-600">{{ json_encode($event->public_data, JSON_UNESCAPED_SLASHES) }}</p>@endif</article>
            @empty
                <p class="pb-4 text-slate-500">No traceability events recorded.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-6 rounded-xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold">Processing and quality</h2>
        @if ($batch->processingRecord)
            <div class="mt-4 grid gap-4 md:grid-cols-4"><div><p class="text-xs text-slate-500">Status</p><p class="font-semibold">{{ $batch->processingRecord->status->value }}</p></div><div><p class="text-xs text-slate-500">Input</p><p class="font-semibold">{{ $batch->processingRecord->input_weight_kg }} kg</p></div><div><p class="text-xs text-slate-500">Output</p><p class="font-semibold">{{ $batch->processingRecord->output_weight_kg ?? '—' }} kg</p></div><div><p class="text-xs text-slate-500">Waste</p><p class="font-semibold">{{ $batch->processingRecord->waste_weight_kg ?? '—' }} kg</p></div></div>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">@foreach ($batch->processingRecord->steps as $step)<article class="rounded-lg border border-slate-200 p-3"><p class="font-semibold">{{ $step->sequence }}. {{ $step->type->value }}</p><p class="text-xs text-slate-500">{{ $step->status }}</p></article>@endforeach</div>
        @else
            <p class="mt-3 text-slate-500">No processing record attached.</p>
        @endif
        <div class="mt-5 space-y-2">@foreach ($batch->inspections as $inspection)<article class="rounded-lg bg-slate-50 p-4"><div class="flex justify-between"><strong>Inspection: {{ $inspection->result->value }}</strong><span class="text-sm text-slate-500">{{ $inspection->inspected_at?->format('Y-m-d H:i') }}</span></div><p class="mt-1 text-sm text-slate-600">Temperature {{ $inspection->product_temperature }} °C · pH {{ $inspection->ph_level ?? '—' }} · {{ $inspection->appearance }} · {{ $inspection->odor }}</p></article>@endforeach</div>
    </section>

    <section class="mt-6 rounded-xl bg-white p-6 shadow-sm">
        <h2 class="text-lg font-bold">Transport and cold chain</h2>
        <div class="mt-4 space-y-4">
            @forelse ($batch->transportTrips as $trip)
                @php($stats = $telemetry->get($trip->id)) @php($tripAlerts = $alerts->get($trip->id))
                <article class="rounded-lg border border-slate-200 p-4"><div class="flex flex-wrap justify-between gap-3"><div><p class="font-semibold">{{ $trip->trip_code }} · {{ $trip->status->value }}</p><p class="text-sm text-slate-500">{{ $trip->origin }} → {{ $trip->destination }} · {{ $trip->vehicle?->registration_number }} · {{ $trip->driver_name }}</p></div><p class="text-sm font-semibold {{ ($tripAlerts->open_alert_count ?? 0) > 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $tripAlerts->open_alert_count ?? 0 }} open alerts</p></div><div class="mt-3 grid gap-3 text-sm sm:grid-cols-4"><div><span class="text-slate-500">Readings</span><br>{{ $stats->reading_count ?? 0 }}</div><div><span class="text-slate-500">Temperature</span><br>{{ $stats ? number_format((float) $stats->minimum_temperature, 2).'–'.number_format((float) $stats->maximum_temperature, 2).' °C' : '—' }}</div><div><span class="text-slate-500">Minimum battery</span><br>{{ $stats?->minimum_battery ?? '—' }}%</div><div><span class="text-slate-500">Last reading</span><br>{{ $stats?->last_recorded_at ?? '—' }}</div></div><div class="mt-3 text-xs text-slate-500">@foreach ($trip->assignments as $assignment)Device: {{ $assignment->device?->display_name }} ({{ $assignment->firebase_sync_status }}) @endforeach</div></article>
            @empty
                <p class="text-slate-500">No transport trip attached.</p>
            @endforelse
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        <section class="rounded-xl bg-white p-6 shadow-sm"><h2 class="font-bold">AI assessment</h2>@if ($batch->latestAiPrediction)<p class="mt-3 text-2xl font-bold">{{ $batch->latestAiPrediction->risk_level }}</p><p class="mt-2 text-sm text-slate-600">{{ $batch->latestAiPrediction->recommendation }}</p><p class="mt-3 text-xs text-slate-500">{{ $batch->latestAiPrediction->provider }} · {{ $batch->latestAiPrediction->model_version }} · {{ $batch->latestAiPrediction->predicted_at?->format('Y-m-d H:i') }}</p>@else<p class="mt-3 text-slate-500">No prediction available.</p>@endif</section>
        <section class="rounded-xl bg-white p-6 shadow-sm"><h2 class="font-bold">Blockchain anchors</h2><div class="mt-3 space-y-2">@forelse ($blockchain as $anchor)<div class="flex justify-between"><span>{{ $anchor->status }}</span><strong>{{ $anchor->anchor_count }}</strong></div>@empty<p class="text-slate-500">No events anchored.</p>@endforelse</div></section>
        <section class="rounded-xl bg-white p-6 shadow-sm"><h2 class="font-bold">Retail position</h2><div class="mt-3 space-y-2">@forelse ($batch->inventoryLots as $lot)<div class="rounded-lg bg-slate-50 p-3"><strong>{{ $lot->location?->name }}</strong><p class="text-sm text-slate-600">{{ str_replace('_', ' ', $lot->status->value) }} · {{ $lot->available_packages }} available · {{ $lot->sold_packages }} sold</p></div>@empty<p class="text-slate-500">No direct retail inventory lot.</p>@endforelse</div></section>
    </div>

    <section class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
        <header class="border-b p-5"><h2 class="font-bold">Batch audit history</h2></header>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="border-b bg-slate-50 text-slate-500"><tr><th class="p-4">Recorded</th><th>Action</th><th>Actor</th><th>Request</th></tr></thead><tbody>@forelse ($auditLogs as $log)<tr class="border-b border-slate-100"><td class="p-4">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td><td>{{ $log->action }}</td><td>{{ $log->actor?->name ?? 'System' }}</td><td class="font-mono text-xs">{{ $log->request_id ?? '—' }}</td></tr>@empty<tr><td colspan="4" class="p-8 text-center text-slate-500">No batch-specific audit entries recorded.</td></tr>@endforelse</tbody></table></div>
    </section>
</x-layouts.admin>
