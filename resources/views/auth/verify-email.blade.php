<x-guest-layout>
    <h2>Verifikasi email</h2>
    <p class="sub">Kami telah mengirim tautan verifikasi ke email Anda. Klik tautan tersebut untuk mengaktifkan akun. Belum menerima email? Kirim ulang di bawah.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success"><x-icon name="check" /><div>Tautan verifikasi baru telah dikirim ke email Anda.</div></div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-block">Kirim ulang email verifikasi</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:.8rem">
        @csrf
        <button type="submit" class="btn btn-ghost btn-block">Keluar</button>
    </form>
</x-guest-layout>
