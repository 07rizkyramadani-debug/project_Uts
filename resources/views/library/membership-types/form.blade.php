@php($editing = $type->exists)
<x-app-layout>
    @include('library._styles')
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editing ? 'Edit Jenis Keanggotaan' : 'Tambah Jenis Keanggotaan' }}</h2></x-slot>
    <div style="padding: 2rem 0;">
        <div class="lib-wrap lib-w-2xl">
            @include('library._flash')
            <form method="POST" action="{{ $editing ? route('membership-types.update', $type) : route('membership-types.store') }}" class="lib-card lib-form">
                @csrf
                @if ($editing) @method('PUT') @endif
                <div class="lib-section-title">Jenis Keanggotaan</div>
                <div>
                    <label for="name">Nama jenis</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $type->name) }}" placeholder="Contoh: Mahasiswa" required>
                </div>
                <div class="lib-grid-2">
                    <div>
                        <label for="max_loans">Maksimal buku dipinjam</label>
                        <input id="max_loans" name="max_loans" type="number" min="1" max="20" value="{{ old('max_loans', $type->max_loans ?? 5) }}" required>
                    </div>
                    <div>
                        <label for="loan_days">Lama pinjam (hari)</label>
                        <input id="loan_days" name="loan_days" type="number" min="1" max="30" value="{{ old('loan_days', $type->loan_days ?? 14) }}" required>
                    </div>
                </div>
                <div class="lib-form-actions">
                    <button type="submit" class="lib-btn lib-btn-primary">💾 Simpan</button>
                    <a href="{{ route('membership-types.index') }}" class="lib-btn lib-btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
