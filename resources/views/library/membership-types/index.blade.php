@php
    $palettes = [['#0ea5e9', '#4f46e5'], ['#059669', '#0d9488'], ['#7c3aed', '#db2777'], ['#b45309', '#dc2626']];
@endphp
<x-app-layout>
    <x-slot name="header">
        <div class="bk-hero">
            <div>
                <h2 class="page-title">Jenis Keanggotaan</h2>
                <p class="page-sub">Atur batas jumlah buku dan lama pinjam untuk setiap jenis anggota.</p>
            </div>
            <div class="bk-hero-side">
                <a href="{{ route('members.index') }}" class="btn btn-ghost">← Anggota</a>
                <a href="{{ route('membership-types.create') }}" class="btn btn-primary">+ Tambah Jenis</a>
            </div>
        </div>
    </x-slot>

    <div class="container">
        @include('library._flash')

        <div class="ms-grid">
            @foreach ($types as $type)
                @php [$g1, $g2] = $palettes[$type->id % count($palettes)]; @endphp
                <article class="ms-card">
                    <div class="ms-top">
                        <span class="mb-avatar ms-ico" style="--g1: {{ $g1 }}; --g2: {{ $g2 }}">🪪</span>
                        <div class="ms-id">
                            <h3 title="{{ $type->name }}">{{ $type->name }}</h3>
                            <span>Maks. {{ $type->max_loans }} buku · {{ $type->loan_days }} hari</span>
                        </div>
                        <span class="lib-badge {{ $type->members_count > 0 ? 'lib-badge-blue' : 'lib-badge-gray' }}">{{ $type->members_count }} anggota</span>
                    </div>
                    <div class="ms-actions">
                        <a href="{{ route('membership-types.edit', $type) }}" class="bk-act bk-act-edit">✎ Edit</a>
                        <form action="{{ route('membership-types.destroy', $type) }}" method="POST" onsubmit="return confirm('Hapus jenis keanggotaan {{ e($type->name) }}?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="bk-act bk-act-del">🗑 Hapus</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-app-layout>
