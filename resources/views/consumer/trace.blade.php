<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Trace {{ $trace['batch']['code'] }}</title>@vite(['resources/css/app.css'])</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<header class="bg-[#073b3a] px-5 py-8 text-white"><div class="mx-auto max-w-4xl"><p class="font-semibold tracking-wide text-cyan-200">{{ $trace['portal']['name'] }} verified origin</p><h1 class="mt-2 text-3xl font-bold">{{ $trace['batch']['code'] }}</h1></div></header>
<main class="mx-auto max-w-4xl space-y-6 p-5 py-8">
    @if($trace['portal']['notice'])<aside class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900" role="status">{{ $trace['portal']['notice'] }}</aside>@endif
    <section class="rounded-2xl border-l-4 {{ $trace['batch']['recalled'] ? 'border-red-600 bg-red-50' : 'border-cyan-600 bg-white' }} p-6 shadow-sm"><p class="text-sm font-semibold {{ $trace['batch']['recalled'] ? 'text-red-700' : 'text-cyan-800' }}">{{ str_replace('_',' ', $trace['verification_status']) }}</p><h2 class="mt-2 text-2xl font-bold">{{ $trace['batch']['species'] }}</h2><p class="italic text-slate-500">{{ $trace['batch']['scientific_name'] }}</p><dl class="mt-5 grid gap-4 sm:grid-cols-3"><div><dt class="text-xs uppercase text-slate-500">Product</dt><dd class="font-medium">{{ $trace['batch']['product_type'] }}</dd></div><div><dt class="text-xs uppercase text-slate-500">Weight</dt><dd class="font-medium">{{ $trace['batch']['weight_kg'] }} kg</dd></div><div><dt class="text-xs uppercase text-slate-500">Catch date</dt><dd class="font-medium">{{ $trace['origin']['catch_date'] }}</dd></div></dl></section>
    <section class="rounded-2xl bg-white p-6 shadow-sm"><h2 class="text-xl font-bold">Origin</h2><p class="mt-3 text-slate-600">{{ $trace['origin']['general_area'] }}</p><p class="text-slate-600">Vessel: {{ $trace['origin']['vessel'] }}</p></section>
    <section class="rounded-2xl bg-white p-6 shadow-sm"><h2 class="text-xl font-bold">Traceability timeline</h2><ol class="mt-5 space-y-5 border-l-2 border-cyan-200 pl-5">@foreach($trace['timeline'] as $event)<li><p class="font-semibold">{{ $event['title'] }}</p><time class="text-sm text-slate-500">{{ \Carbon\Carbon::parse($event['occurred_at'])->format('d M Y, H:i') }} UTC</time></li>@endforeach</ol></section>
    <p class="text-center text-xs text-slate-500">Verified {{ $trace['verified_at'] }} · Exact coordinates and personal information are protected.@if($trace['portal']['support_email']) Support: <a class="underline" href="mailto:{{ $trace['portal']['support_email'] }}">{{ $trace['portal']['support_email'] }}</a>.@endif</p>
</main>
</body>
</html>
