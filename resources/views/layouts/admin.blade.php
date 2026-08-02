<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $title ?? $platformName.' Admin' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<a href="#main-content" class="sr-only z-50 rounded bg-white px-4 py-2 text-slate-900 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to main content</a>
<div class="min-h-screen lg:flex">
    <header class="bg-[#073b3a] px-5 py-4 text-white lg:hidden">
        <div class="flex items-center justify-between"><a href="{{ route('admin.dashboard') }}" class="text-xl font-bold">{{ $platformName }}</a><span class="text-xs text-cyan-200">Operations Console</span></div>
        <details class="mt-3"><summary class="cursor-pointer rounded-lg border border-white/30 px-3 py-2 font-medium">Navigation</summary><div class="mt-3">@include('admin._primary-nav')</div></details>
    </header>
    <aside class="hidden min-h-screen bg-[#073b3a] px-6 py-7 text-white lg:block lg:w-64 lg:shrink-0">
        <a href="{{ route('admin.dashboard') }}" class="text-xl font-bold tracking-tight">{{ $platformName }}</a>
        <p class="mt-1 text-xs text-cyan-200">Operations Console</p>
        <div class="mt-10">@include('admin._primary-nav')</div>
        <form method="post" action="{{ route('admin.logout') }}" class="mt-10">
            @csrf
            <button class="text-sm text-cyan-100 hover:text-white">Sign out</button>
        </form>
    </aside>
    <main id="main-content" class="admin-main min-w-0 flex-1 p-5 lg:p-10" tabindex="-1">
        @if (session('success'))
            <div class="mb-6 rounded-lg bg-emerald-100 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-6 rounded-lg bg-red-100 px-4 py-3 text-sm text-red-900" role="alert">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        {{ $slot }}
    </main>
</div>
</body>
</html>
