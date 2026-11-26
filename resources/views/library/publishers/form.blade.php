@php($editing = $publisher->exists)
<x-app-layout>
    @include('library._styles')
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editing ? 'Edit Penerbit' : 'Tambah Penerbit' }}</h2></x-slot>
    <div style="padding: 2rem 0;">
        <div class="lib-wrap lib-w-2xl">
            @include('library._flash')
            <form method="POST" action="{{ $editing ? route('publishers.update', $publisher) : route('publishers.store') }}" class="lib-card lib-form">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="lib-section-title">Data Penerbit</div>
                <div>
                    <label for="name">Nama penerbit</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $publisher->name) }}" placeholder="Contoh: Penerbit Informatika" required>
                </div>
                <div>
                    <label for="city">Kota (opsional)</label>
                    <input id="city" name="city" type="text" value="{{ old('city', $publisher->city) }}" placeholder="Contoh: Bandung">
                </div>
                <div class="lib-form-actions">
                    <button type="submit" class="lib-btn lib-btn-primary">💾 Simpan</button>
                    <a href="{{ route('publishers.index') }}" class="lib-btn lib-btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
