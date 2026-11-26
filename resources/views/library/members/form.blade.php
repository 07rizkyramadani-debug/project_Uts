@php($editing = $member->exists)
<x-app-layout>
    @include('library._styles')
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editing ? 'Edit Anggota' : 'Tambah Anggota' }}</h2></x-slot>
    <div style="padding: 2rem 0;">
        <div class="lib-wrap lib-w-2xl">
            @include('library._flash')
            <form method="POST" action="{{ $editing ? route('members.update', $member) : route('members.store') }}" class="lib-card lib-form">
                @csrf
                @if ($editing) @method('PUT') @endif

                <div class="lib-section-title">Data Anggota</div>
                <div>
                    <label for="name">Nama lengkap</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $member->name) }}" placeholder="Nama anggota" required>
                </div>
                <div class="lib-grid-2">
                    <div>
                        <label for="email">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email', $member->email) }}" placeholder="nama@email.com" required>
                    </div>
                    <div>
                        <label for="phone">No. telepon</label>
                        <input id="phone" name="phone" type="text" value="{{ old('phone', $member->phone) }}" placeholder="08xxxxxxxxxx">
                    </div>
                </div>
                <div>
                    <label for="membership_type_id">Jenis keanggotaan</label>
                    <select id="membership_type_id" name="membership_type_id">
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(old('membership_type_id', $member->membership_type_id ?? ($types->firstWhere('slug', 'umum')?->id)) == $type->id)>{{ $type->name }} — maks. {{ $type->max_loans }} buku, {{ $type->loan_days }} hari</option>
                        @endforeach
                    </select>
                    <div class="lib-hint">Menentukan batas jumlah buku dan lama pinjam anggota.</div>
                </div>

                @if ($editing)
                    <div>
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="active" @selected($member->status !== 'suspended')>Aktif</option>
                            <option value="suspended" @selected($member->status === 'suspended')>Ditangguhkan</option>
                        </select>
                    </div>
                @endif

                <div class="lib-form-actions">
                    <button type="submit" class="lib-btn lib-btn-primary">💾 Simpan</button>
                    <a href="{{ route('members.index') }}" class="lib-btn lib-btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
