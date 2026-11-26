@php
    $user = Auth::user();
    $initial = strtoupper(mb_substr($user->name, 0, 1));
    $openStockReports = $user->isAdmin() ? \Modules\Catalog\Models\BookStockReport::open()->count() : 0;
    $links = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'grid', 'show' => true],
        ['route' => 'books.index', 'match' => 'books.*', 'label' => 'Buku', 'icon' => 'book', 'show' => true],
        ['route' => 'members.index', 'match' => 'members.*', 'label' => 'Anggota', 'icon' => 'users', 'show' => $user->canManageCirculation()],
        ['route' => 'loans.index', 'match' => 'loans.*', 'label' => 'Peminjaman', 'icon' => 'swap', 'show' => $user->canManageCirculation()],
        ['route' => 'authors.index', 'match' => ['authors.*', 'categories.*', 'publishers.*'], 'label' => 'Data Master', 'icon' => 'layers', 'show' => $user->isAdmin()],
        ['route' => 'stock-reports.index', 'match' => 'stock-reports.*', 'label' => 'Laporan Stok'.($openStockReports > 0 ? " ({$openStockReports})" : ''), 'icon' => 'alert', 'show' => $user->isAdmin()],
        ['route' => 'my-loans.index', 'match' => 'my-loans.*', 'label' => 'Pinjaman Saya', 'icon' => 'swap', 'show' => $user->isUser()],
    ];
@endphp
<nav class="topbar" x-data="{ open: false }">
    <div class="topbar-inner">
        <x-brand href="{{ route('dashboard') }}" />

        <div class="nav-links">
            @foreach ($links as $l)
                @if ($l['show'])
                    <a href="{{ route($l['route']) }}" class="{{ request()->routeIs(...(array) $l['match']) ? 'active' : '' }}"><x-icon :name="$l['icon']" />{{ $l['label'] }}</a>
                @endif
            @endforeach
        </div>

        <div class="topbar-spacer"></div>

        <div class="user-area">
            <x-dropdown align="right">
                <x-slot name="trigger">
                    <button type="button" class="user-chip">
                        <span class="avatar">{{ $initial }}</span>
                        <span class="who"><b>{{ $user->name }}</b><span>{{ $user->role }}</span></span>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')"><x-icon name="user" style="width:1rem;height:1rem" /> Profil Saya</x-dropdown-link>
                    @if ($user->isAdmin())
                        <x-dropdown-link :href="route('admin.dashboard')"><x-icon name="shield" style="width:1rem;height:1rem" /> Dashboard Admin</x-dropdown-link>
                    @endif
                    <div class="dd-sep"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dd-item"><x-icon name="logout" style="width:1rem;height:1rem" /> Keluar</button>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>

        <button type="button" class="nav-toggle" @click="open = ! open" aria-label="Menu">
            <span x-show="! open"><x-icon name="menu" /></span>
            <span x-show="open" style="display:none"><x-icon name="x" /></span>
        </button>
    </div>

    <div class="mobile-nav" :class="{ 'open': open }">
        <div class="m-user"><b>{{ $user->name }}</b> &middot; {{ ucfirst($user->role) }}</div>
        @foreach ($links as $l)
            @if ($l['show'])
                <a href="{{ route($l['route']) }}" class="{{ request()->routeIs(...(array) $l['match']) ? 'active' : '' }}">{{ $l['label'] }}</a>
            @endif
        @endforeach
        <a href="{{ route('profile.edit') }}">Profil Saya</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Keluar</button></form>
    </div>
</nav>
