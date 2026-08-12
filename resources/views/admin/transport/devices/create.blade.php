<x-layouts.admin title="Add IoT Device | FishTrace">
    @include('admin.transport._nav')
    <a href="{{ route('admin.transport.devices.index') }}" class="text-sm text-cyan-700">← IoT devices</a>
    <h1 class="mt-2 text-3xl font-bold">Add IoT device</h1>
    <p class="mt-1 text-slate-500">Register the physical unit, its owner, and supported telemetry.</p>
    @if ($errors->any())<div class="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert"><strong>Check the device details.</strong><ul class="mt-2 list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="post" action="{{ route('admin.transport.devices.store') }}" class="mt-6 max-w-4xl rounded-xl bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-5 md:grid-cols-2">
            <label><span class="text-sm font-medium">Transport organization</span><select name="organization_id" required class="mt-1 w-full rounded-lg border-slate-300"><option value="">Select organization</option>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected(old('organization_id')===$organization->id)>{{ $organization->name }}</option>@endforeach</select></label>
            <label><span class="text-sm font-medium">Display name</span><input name="display_name" required maxlength="120" value="{{ old('display_name') }}" placeholder="Reefer Sensor 02" class="mt-1 w-full rounded-lg border-slate-300"></label>
            <label><span class="text-sm font-medium">Device code</span><input name="device_code" required maxlength="50" value="{{ old('device_code') }}" placeholder="IOT-002" class="mt-1 w-full rounded-lg border-slate-300"></label>
            <label><span class="text-sm font-medium">Serial number</span><input name="serial_number" required maxlength="100" value="{{ old('serial_number') }}" placeholder="ESP32-FT-0002" class="mt-1 w-full rounded-lg border-slate-300"></label>
            <label><span class="text-sm font-medium">Firmware version</span><input name="firmware_version" maxlength="50" value="{{ old('firmware_version') }}" placeholder="1.2.0" class="mt-1 w-full rounded-lg border-slate-300"></label>
        </div>
        <fieldset class="mt-7"><legend class="font-semibold">Sensor capabilities</legend><div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach(['supports_product_temperature'=>'Product temperature','supports_air_temperature'=>'Air temperature','supports_humidity'=>'Humidity','supports_gps'=>'GPS','supports_door_sensor'=>'Door sensor'] as $field=>$label)<label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3"><input type="hidden" name="{{ $field }}" value="0"><input type="checkbox" name="{{ $field }}" value="1" class="rounded border-slate-300 text-cyan-700" @checked((bool) old($field, true))><span>{{ $label }}</span></label>@endforeach
        </div></fieldset>
        <label class="mt-7 flex items-start gap-3 rounded-lg bg-cyan-50 p-4"><input type="hidden" name="provision_now" value="0"><input type="checkbox" name="provision_now" value="1" class="mt-1 rounded border-slate-300 text-cyan-700" @checked((bool) old('provision_now', true))><span><strong class="block">Provision Firebase access now</strong><span class="text-sm text-slate-600">A one-time password will be generated and shown only on the next screen.</span></span></label>
        <div class="mt-7 flex gap-3"><button class="rounded-lg bg-[#075e63] px-5 py-2.5 font-semibold text-white">Create device</button><a href="{{ route('admin.transport.devices.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5">Cancel</a></div>
    </form>
</x-layouts.admin>
