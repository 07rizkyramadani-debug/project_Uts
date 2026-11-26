@php($editing = $author->exists)
<x-app-layout>
    @include('library._styles')
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editing ? 'Edit Penulis' : 'Tambah Penulis' }}</h2></x-slot>
    <div style="padding: 2rem 0;">
        <div class="lib-wrap lib-w-2xl">
            @include('library._flash')
            <form method="POST" action="{{ $editing ? route('authors.update', $author) : route('authors.store') }}" class="lib-card lib-form">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="lib-section-title">Data Penulis</div>
                <div>
                    <label for="name">Nama penulis</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $author->name) }}" placeholder="Contoh: Andrea Hirata" required>
                </div>
                <div>
                    <label for="bio">Biografi singkat (opsional)</label>
                    <textarea id="bio" name="bio" rows="4" placeholder="Ringkasan singkat tentang penulis">{{ old('bio', $author->bio) }}</textarea>
                </div>
                <div class="lib-form-actions">
                    <button type="submit" class="lib-btn lib-btn-primary">💾 Simpan</button>
                    <a href="{{ route('authors.index') }}" class="lib-btn lib-btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
