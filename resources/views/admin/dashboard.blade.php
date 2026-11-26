<x-app-layout>
    <x-slot name="header">
        <h2>Dashboard Admin</h2>
        <p class="page-sub">Statistik pengguna sistem dan ringkasan hak akses berdasarkan peran.</p>
    </x-slot>

    <div class="container stack">
        <section class="stats">
            <div class="stat tone-indigo"><div class="stat-ico"><x-icon name="users" /></div><div class="stat-label">Total Pengguna</div><div class="stat-value">{{ $totalUsers }}</div></div>
            <div class="stat tone-rose"><div class="stat-ico"><x-icon name="shield" /></div><div class="stat-label">Admin</div><div class="stat-value">{{ $totalAdmins }}</div></div>
            <div class="stat tone-sky"><div class="stat-ico"><x-icon name="swap" /></div><div class="stat-label">Pustakawan</div><div class="stat-value">{{ $totalPustakawan }}</div></div>
            <div class="stat tone-green"><div class="stat-ico"><x-icon name="user" /></div><div class="stat-label">User Biasa</div><div class="stat-value">{{ $totalRegularUsers }}</div></div>
        </section>

        <div class="dh-cols">
            <section class="card">
                <div class="sec-head"><h3>Komposisi pengguna</h3><span>berdasarkan peran</span></div>
                @php
                    $segs = [['Admin', $totalAdmins, '#ef4444'], ['Pustakawan', $totalPustakawan, '#0ea5e9'], ['User', $totalRegularUsers, '#10b981']];
                    $sum = max(1, $totalUsers); $acc = 0; $stops = [];
                    foreach ($segs as [$l, $n, $c]) { if ($n > 0) { $f = $acc / $sum * 100; $acc += $n; $stops[] = "$c $f% ".($acc / $sum * 100).'%'; } }
                    $gradient = $stops ? 'conic-gradient('.implode(', ', $stops).')' : 'conic-gradient(#e5e7eb 0 100%)';
                @endphp
                <div class="dh-donut-wrap">
                    <div class="dh-donut" style="background: {{ $gradient }}"><span><b>{{ $totalUsers }}</b>pengguna</span></div>
                    <ul class="dh-legend">
                        @foreach ($segs as [$l, $n, $c])
                            <li><i style="background: {{ $c }}"></i>{{ $l }}<b>{{ $n }}</b></li>
                        @endforeach
                    </ul>
                </div>
            </section>

            <section class="card">
                <div class="sec-head"><h3>Hak akses per peran</h3><span>RBAC</span></div>
                <div class="dh-perm">
                    <div><span class="role-pill">admin</span><p>Kelola buku, anggota, data master, peminjaman, dan data terhapus.</p></div>
                    <div><span class="role-pill">pustakawan</span><p>Setujui pengajuan, catat peminjaman dan pengembalian, lihat anggota.</p></div>
                    <div><span class="role-pill">user</span><p>Telusuri katalog, ajukan pinjam, lihat Pinjaman Saya dan riwayat.</p></div>
                </div>
            </section>
        </div>

        <section class="card">
            <div class="sec-head"><h3>Pengguna terbaru</h3><span>6 akun terakhir</span></div>
            <div style="overflow-x:auto">
                <table class="lib-table">
                    <thead><tr><th>Nama</th><th>Email</th><th class="text-center">Peran</th><th>Bergabung</th></tr></thead>
                    <tbody>
                        @foreach ($recentUsers as $account)
                            <tr>
                                <td><span class="dh-user"><span class="avatar">{{ strtoupper(mb_substr($account->name, 0, 1)) }}</span>{{ $account->name }}</span></td>
                                <td>{{ $account->email }}</td>
                                <td class="text-center"><span class="role-pill">{{ $account->role }}</span></td>
                                <td>{{ $account->created_at->locale('id')->translatedFormat('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-app-layout>
