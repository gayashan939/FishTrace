<x-layouts.admin title="Edit Organization | FishTrace">
    <a href="{{ route('admin.organizations.show', $organization) }}" class="text-sm text-cyan-700">← Organization details</a>
    <h1 class="mt-2 text-3xl font-bold">Edit {{ $organization->name }}</h1>
    <form method="post" action="{{ route('admin.organizations.update', $organization) }}" class="mt-6 rounded-xl bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        @include('admin.organizations._form')
    </form>
</x-layouts.admin>
