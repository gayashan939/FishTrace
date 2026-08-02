<nav class="mt-6 flex flex-wrap gap-2" aria-label="Settings navigation">
    @foreach([
        ['Alert rules', 'admin.settings.alert-rules.index', 'admin.settings.alert-rules.*'],
        ['System', 'admin.settings.system.edit', 'admin.settings.system.*'],
        ['Profile', 'admin.settings.profile.edit', 'admin.settings.profile.*'],
        ['Security', 'admin.settings.security.index', 'admin.settings.security.*'],
    ] as [$label, $route, $pattern])
        <a href="{{ route($route) }}" class="rounded-lg px-4 py-2 text-sm font-semibold {{ request()->routeIs($pattern) ? 'bg-[#075e63] text-white' : 'bg-white text-slate-700' }}" @if(request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
