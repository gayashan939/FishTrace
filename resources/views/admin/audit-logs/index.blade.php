<x-layouts.admin title="Audit logs · FishTrace">
    <header class="mb-7">
        <p class="text-sm font-medium text-cyan-700">Security and accountability</p>
        <h1 class="text-3xl font-bold">Audit logs</h1>
        <p class="mt-1 text-slate-500">Immutable, redacted activity across regulated organizations.</p>
    </header>

    <form method="get" class="grid gap-3 rounded-xl bg-white p-5 shadow-sm md:grid-cols-2 xl:grid-cols-5">
        <label class="text-sm font-medium">From<input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
        <label class="text-sm font-medium">To<input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
        <label class="text-sm font-medium">Organization<select name="organization_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"><option value="">All organizations</option>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected(($filters['organization_id'] ?? '') === $organization->id)>{{ $organization->name }}</option>@endforeach</select></label>
        <label class="text-sm font-medium">Action<input name="action" value="{{ $filters['action'] ?? '' }}" placeholder="e.g. AUTH_LOGIN_SUCCEEDED" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
        <div class="flex items-end gap-2"><button class="rounded-lg bg-[#087f8c] px-4 py-2 font-semibold text-white">Filter</button><a href="{{ route('admin.audit-logs') }}" class="rounded-lg border border-slate-300 px-4 py-2">Clear</a></div>
    </form>

    <section class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-slate-600"><tr><th class="px-4 py-3">Time</th><th>Action</th><th>Actor / organization</th><th>Entity</th><th>Request</th><th class="pr-4">Changes</th></tr></thead>
                <tbody>
                @forelse($auditLogs as $log)
                    <tr class="border-b border-slate-100 align-top">
                        <td class="whitespace-nowrap px-4 py-3">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                        <td class="font-semibold text-[#075e63]">{{ $log->action }}</td>
                        <td>{{ $log->actor?->name ?? 'System' }}<br><span class="text-xs text-slate-500">{{ $log->organization?->name ?? 'Unscoped' }}</span></td>
                        <td>{{ $log->auditable_type ? class_basename($log->auditable_type) : '—' }}<br><span class="font-mono text-xs text-slate-500">{{ $log->auditable_id }}</span></td>
                        <td><span class="font-mono text-xs">{{ $log->request_id ?? '—' }}</span><br><span class="text-xs text-slate-500">{{ $log->ip_address }}</span></td>
                        <td class="max-w-md pr-4"><details><summary class="cursor-pointer text-cyan-700">View redacted state</summary><pre class="mt-2 max-h-48 overflow-auto rounded bg-slate-900 p-3 text-xs text-slate-100">{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">No audit records matched these filters.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $auditLogs->links() }}</div>
    </section>
</x-layouts.admin>
