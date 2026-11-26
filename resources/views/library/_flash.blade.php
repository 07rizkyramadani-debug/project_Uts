@if (session('success'))
    <div class="alert alert-success" role="alert"><x-icon name="check" /><div>{{ session('success') }}</div></div>
@endif
@if ($errors->any())
    <div class="alert alert-error" role="alert">
        <x-icon name="alert" />
        <div>
            <strong>Terjadi kesalahan:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
