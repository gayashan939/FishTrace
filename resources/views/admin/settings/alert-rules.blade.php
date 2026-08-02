@php
    $labels = [
        'HIGH_TEMPERATURE' => ['Product temperature', 'Warning after a sustained high reading; critical breaches trigger immediately.', '°C'],
        'LOW_TEMPERATURE' => ['Low product temperature', 'Warn when product temperature falls below the safe minimum.', '°C'],
        'LOW_BATTERY' => ['Device battery', 'Warn and escalate when the assigned device battery falls below configured levels.', '%'],
        'DEVICE_OFFLINE' => ['Device offline', 'Notify when an assigned device has not reported within this duration.', 'minutes'],
        'GPS_UNAVAILABLE' => ['GPS unavailable', 'Warn when a telemetry reading does not contain a complete position.', 'minutes'],
        'DOOR_OPENED' => ['Cargo door opened', 'Warn when the device reports that the cargo door is open.', 'minutes'],
    ];
@endphp
<x-layouts.admin title="Alert rules | FishTrace">
    <header><p class="text-sm font-medium text-cyan-700">System settings</p><h1 class="text-3xl font-bold">Cold-chain alert rules</h1><p class="mt-1 max-w-3xl text-slate-500">These global defaults drive telemetry evaluation for every transporter unless an organization-specific override exists.</p></header>
    @include('admin.settings._nav')
    <form method="post" action="{{ route('admin.settings.alert-rules.update') }}" class="mt-7 space-y-5">
        @csrf @method('PUT')
        @foreach($rules as $rule)
            @php([$title, $description, $unit] = $labels[$rule['rule_type']])
            <section class="rounded-xl bg-white p-5 shadow-sm" aria-labelledby="rule-{{ $rule['rule_type'] }}">
                <div class="flex flex-wrap items-start justify-between gap-4"><div><h2 id="rule-{{ $rule['rule_type'] }}" class="text-lg font-bold">{{ $title }}</h2><p class="mt-1 text-sm text-slate-500">{{ $description }}</p></div><label class="flex items-center gap-3"><input type="hidden" name="rules[{{ $rule['rule_type'] }}][is_enabled]" value="0"><input type="checkbox" name="rules[{{ $rule['rule_type'] }}][is_enabled]" value="1" class="rounded border-slate-300 text-cyan-700" @checked((bool) old('rules.'.$rule['rule_type'].'.is_enabled', $rule['is_enabled']))><span class="text-sm font-semibold">Enabled</span></label></div>
                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <label><span class="text-sm font-medium">Warning threshold</span><span class="mt-1 flex items-center gap-2"><input type="number" step="0.001" name="rules[{{ $rule['rule_type'] }}][warning_threshold]" value="{{ old('rules.'.$rule['rule_type'].'.warning_threshold', $rule['warning_threshold']) }}" class="w-full rounded-lg border-slate-300" @if(in_array($rule['rule_type'], ['DEVICE_OFFLINE','GPS_UNAVAILABLE','DOOR_OPENED'], true)) disabled @endif><span class="text-sm text-slate-500">{{ $unit }}</span></span></label>
                    <label><span class="text-sm font-medium">Critical threshold</span><span class="mt-1 flex items-center gap-2"><input type="number" step="0.001" name="rules[{{ $rule['rule_type'] }}][critical_threshold]" value="{{ old('rules.'.$rule['rule_type'].'.critical_threshold', $rule['critical_threshold']) }}" class="w-full rounded-lg border-slate-300" @if(!in_array($rule['rule_type'], ['HIGH_TEMPERATURE','LOW_BATTERY'], true)) disabled @endif><span class="text-sm text-slate-500">{{ $unit }}</span></span></label>
                    <label><span class="text-sm font-medium">Duration</span><span class="mt-1 flex items-center gap-2"><input type="number" min="0" max="1440" name="rules[{{ $rule['rule_type'] }}][duration_minutes]" value="{{ old('rules.'.$rule['rule_type'].'.duration_minutes', $rule['duration_minutes']) }}" class="w-full rounded-lg border-slate-300"><span class="text-sm text-slate-500">minutes</span></span></label>
                </div>
            </section>
        @endforeach
        <div class="sticky bottom-4 flex justify-end"><button class="rounded-lg bg-[#075e63] px-6 py-3 font-semibold text-white shadow-lg">Save alert rules</button></div>
    </form>
</x-layouts.admin>
