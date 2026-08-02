<x-layouts.admin title="Add Organization | FishTrace">
    <a href="{{ route('admin.organizations.index') }}" class="text-sm text-cyan-700">← Organizations</a>
    <h1 class="mt-2 text-3xl font-bold">Add organization</h1>
    <form method="post" action="{{ route('admin.organizations.store') }}" class="mt-6 rounded-xl bg-white p-6 shadow-sm">
        @csrf
        @include('admin.organizations._form')
    </form>
</x-layouts.admin>
