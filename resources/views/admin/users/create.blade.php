<x-layouts.admin title="Add User | FishTrace">
    <a href="{{ route('admin.users.index') }}" class="text-sm text-cyan-700">← Users</a>
    <h1 class="mt-2 text-3xl font-bold">Add user</h1>
    <form method="post" action="{{ route('admin.users.store') }}" class="mt-6 rounded-xl bg-white p-6 shadow-sm">
        @csrf
        @include('admin.users._form')
    </form>
</x-layouts.admin>
