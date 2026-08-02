<x-layouts.admin title="Edit User | FishTrace">
    <a href="{{ route('admin.users.show', $managedUser) }}" class="text-sm text-cyan-700">← User details</a>
    <h1 class="mt-2 text-3xl font-bold">Edit {{ $managedUser->name }}</h1>
    <p class="mt-1 text-slate-500">Changing access revokes the user's existing sessions.</p>
    <form method="post" action="{{ route('admin.users.update', $managedUser) }}" class="mt-6 rounded-xl bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @include('admin.users._form')
    </form>
</x-layouts.admin>
