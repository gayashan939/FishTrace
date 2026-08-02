<x-layouts.admin title="{{ $managedUser->name }} | FishTrace">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-cyan-700">← Users</a>
            <h1 class="mt-2 text-3xl font-bold">{{ $managedUser->name }}</h1>
            <p class="text-slate-500">{{ $managedUser->email }}</p>
        </div>
        <a href="{{ route('admin.users.edit', $managedUser) }}" class="rounded-lg bg-[#075e63] px-4 py-2 font-semibold text-white">Edit access</a>
    </div>
    <section class="mt-6 grid gap-4 md:grid-cols-3">
        <article class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Account</p>
            <p class="mt-2 font-semibold">{{ $managedUser->status }}{{ $managedUser->isLocked() ? ' · LOCKED' : '' }}</p>
            <p class="mt-1 text-sm text-slate-500">Failed logins: {{ $managedUser->failed_login_count }}</p>
        </article>
        <article class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Roles</p>
            <p class="mt-2 font-semibold">{{ $managedUser->roles->pluck('name')->join(', ') }}</p>
        </article>
        <article class="rounded-xl bg-white p-5 shadow-sm">
            <p class="text-sm text-slate-500">Sessions</p>
            <p class="mt-2 font-semibold">{{ $managedUser->tokens_count }} API tokens</p>
            <p class="mt-1 text-sm text-slate-500">Last login: {{ $managedUser->last_login_at?->format('Y-m-d H:i') ?? 'Never' }}</p>
        </article>
    </section>
    <section class="mt-6 rounded-xl bg-white p-6 shadow-sm">
        <h2 class="font-bold">Organizations</h2>
        <div class="mt-3 space-y-2">
            @foreach ($managedUser->organizations as $organization)
                <div class="flex justify-between rounded-lg border border-slate-200 p-3">
                    <span>{{ $organization->name }} <small class="text-slate-500">{{ $organization->code }} · {{ $organization->type }}</small></span>
                    @if ($organization->pivot->is_primary)
                        <span class="text-xs font-semibold text-cyan-700">PRIMARY</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
    <section class="mt-6 rounded-xl bg-white p-6 shadow-sm">
        <h2 class="font-bold">Security actions</h2>
        <div class="mt-4 flex flex-wrap gap-3">
            @if ($managedUser->status === 'ACTIVE')
                <form method="post" action="{{ route('admin.users.deactivate', $managedUser) }}">
                    @csrf
                    <button class="rounded-lg border border-red-300 px-4 py-2 text-red-700">Disable account</button>
                </form>
            @else
                <form method="post" action="{{ route('admin.users.activate', $managedUser) }}">
                    @csrf
                    <button class="rounded-lg border border-emerald-300 px-4 py-2 text-emerald-700">Activate account</button>
                </form>
            @endif
            @if ($managedUser->isLocked())
                <form method="post" action="{{ route('admin.users.unlock', $managedUser) }}">
                    @csrf
                    <button class="rounded-lg border border-slate-300 px-4 py-2">Unlock</button>
                </form>
            @endif
            <form method="post" action="{{ route('admin.users.revoke-sessions', $managedUser) }}">
                @csrf
                <button class="rounded-lg border border-slate-300 px-4 py-2">Revoke sessions</button>
            </form>
        </div>
        <form method="post" action="{{ route('admin.users.reset-password', $managedUser) }}" class="mt-6 grid max-w-2xl gap-3 md:grid-cols-3">
            @csrf
            <input type="password" name="password" required placeholder="New password" class="rounded-lg border-slate-300">
            <input type="password" name="password_confirmation" required placeholder="Confirm password" class="rounded-lg border-slate-300">
            <button class="rounded-lg bg-slate-800 px-4 py-2 text-white">Reset password</button>
        </form>
    </section>
</x-layouts.admin>
