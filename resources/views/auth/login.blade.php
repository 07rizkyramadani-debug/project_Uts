<x-guest-layout>
    <h2>Selamat datang kembali</h2>
    <p class="sub">Masuk untuk mengelola koleksi dan peminjaman perpustakaan.</p>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="field">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" placeholder="nama@email.com" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="field">
            <x-input-label for="password" value="Password" />
            <div class="pw-wrap">
                <x-text-input id="password" type="password" name="password" placeholder="Masukkan password" required autocomplete="current-password" />
                <button type="button" class="pw-toggle" onclick="const i=document.getElementById('password');const s=i.type==='password';i.type=s?'text':'password';this.textContent=s?'Sembunyi':'Lihat';">Lihat</button>
            </div>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="row-between">
            <label class="check"><input id="remember_me" type="checkbox" name="remember"> Ingat saya</label>
            @if (Route::has('password.request'))
                <a class="link" href="{{ route('password.request') }}">Lupa password?</a>
            @endif
        </div>

        <button type="submit" class="btn btn-primary btn-block">Masuk</button>
    </form>

    @if (Route::has('register'))
        <p class="auth-switch">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></p>
    @endif
</x-guest-layout>
