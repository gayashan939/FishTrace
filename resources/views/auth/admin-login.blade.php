<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FishTrace Admin</title>@vite(['resources/css/app.css'])</head>
<body class="grid min-h-screen place-items-center bg-slate-100 p-6">
<main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm">
    <div class="mb-8"><p class="text-sm font-semibold uppercase tracking-widest text-cyan-700">FishTrace</p><h1 class="mt-2 text-3xl font-bold text-slate-900">Operations console</h1><p class="mt-2 text-sm text-slate-500">Secure administrator access</p></div>
    <form method="post" action="{{ route('admin.login.store') }}" class="space-y-5">
        @csrf
        <label class="block text-sm font-medium">Email<input name="email" type="email" value="{{ old('email') }}" required autofocus class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-600 focus:outline-none"></label>
        <label class="block text-sm font-medium">Password<input name="password" type="password" required class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2.5 focus:border-cyan-600 focus:outline-none"></label>
        @if(session('login_error'))<p class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ session('login_error') }}</p>@elseif($errors->any())<p class="rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</p>@endif
        <button class="w-full rounded-lg bg-[#087f8c] px-4 py-3 font-semibold text-white hover:bg-[#066a74]">Sign in</button>
    </form>
</main>
</body>
</html>
