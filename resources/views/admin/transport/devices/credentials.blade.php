<x-layouts.admin title="Device Credentials | FishTrace">
    @include('admin.transport._nav')
    <div class="mx-auto max-w-3xl">
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-6"><p class="text-sm font-semibold uppercase tracking-wide text-amber-800">One-time credentials</p><h1 class="mt-2 text-3xl font-bold">{{ $device->display_name }} is ready</h1><p class="mt-2 text-amber-900">Store these credentials in the device's protected setup flow now. The password cannot be displayed again.</p></div>
        <dl class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="border-b p-5"><dt class="text-sm text-slate-500">Device code</dt><dd class="mt-1 font-mono font-semibold">{{ $device->device_code }}</dd></div>
            <div class="border-b p-5"><dt class="text-sm text-slate-500">Firebase UID</dt><dd class="mt-1 break-all font-mono">{{ $credentials['firebase_uid'] }}</dd></div>
            <div class="border-b p-5"><dt class="text-sm text-slate-500">Firebase email</dt><dd class="mt-1 break-all font-mono">{{ $credentials['firebase_email'] }}</dd></div>
            <div class="p-5"><dt class="text-sm text-slate-500">Firebase password</dt><dd class="mt-1 break-all rounded-lg bg-slate-900 p-4 font-mono text-white" data-sensitive>{{ $credentials['firebase_password'] }}</dd></div>
        </dl>
        <p class="mt-4 text-sm text-red-700">Do not email, log, or screenshot production credentials. Leaving this page permanently hides the password.</p>
        <a href="{{ route('admin.transport.devices.show', $device) }}" class="mt-6 inline-block rounded-lg bg-[#075e63] px-5 py-2.5 font-semibold text-white">Continue to device</a>
    </div>
</x-layouts.admin>
