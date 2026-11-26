<x-guest-layout>
    <h2>Lupa password?</h2>
    <p class="sub">Masukkan email Anda dan kami kirimkan tautan untuk mengatur ulang password.</p>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="field">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" placeholder="nama@email.com" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <button type="submit" class="btn btn-primary btn-block">Kirim tautan reset</button>
    </form>

    <p class="auth-switch"><a href="{{ route('login') }}">&larr; Kembali ke halaman masuk</a></p>
</x-guest-layout>
