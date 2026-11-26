<x-app-layout>
    <x-slot name="header">
        <h2>Dashboard</h2>
        <p class="page-sub">{{ $today }}</p>
    </x-slot>

    <div class="container stack">
        @include('dashboard._hero')

        @if ($mode === 'staff')
            @include('dashboard._staff')
        @else
            @include('dashboard._member')
        @endif
    </div>
</x-app-layout>
