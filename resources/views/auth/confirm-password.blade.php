<x-guest-layout>
    <h2>Konfirmasi password</h2>
    <p class="sub">Ini area aman. Masukkan password Anda untuk melanjutkan.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <div class="field">
            <x-input-label for="password" value="Password" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <button type="submit" class="btn btn-primary btn-block">Konfirmasi</button>
    </form>
</x-guest-layout>
