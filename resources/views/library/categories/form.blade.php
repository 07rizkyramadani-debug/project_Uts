@php($editing = $category->exists)
<x-app-layout>
    @include('library._styles')
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editing ? 'Edit Kategori' : 'Tambah Kategori' }}</h2></x-slot>
    <div style="padding: 2rem 0;">
        <div class="lib-wrap lib-w-2xl">
            @include('library._flash')
            <form method="POST" action="{{ $editing ? route('categories.update', $category) : route('categories.store') }}" class="lib-card lib-form">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="lib-section-title">Data Kategori</div>
                <div>
                    <label for="name">Nama kategori</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $category->name) }}" placeholder="Contoh: Pemrograman" required>
                </div>
                <div>
                    <label for="parent_id">Kategori induk (opsional)</label>
                    <select id="parent_id" name="parent_id">
                        <option value="">- Kategori utama -</option>
                        @foreach ($parents as $parent)
                            <option value="{{ $parent->id }}" @selected(old('parent_id', $category->parent_id ?? request('parent_id')) == $parent->id)>{{ $parent->name }}</option>
                        @endforeach
                    </select>
                    <div class="lib-hint">Pilih induk untuk menjadikannya sub-kategori (maksimal dua tingkat).</div>
                </div>
                <div>
                    <label for="description">Deskripsi (opsional)</label>
                    <textarea id="description" name="description" rows="3" placeholder="Keterangan singkat kategori">{{ old('description', $category->description) }}</textarea>
                </div>
                <div class="lib-form-actions">
                    <button type="submit" class="lib-btn lib-btn-primary">💾 Simpan</button>
                    <a href="{{ route('categories.index') }}" class="lib-btn lib-btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
