@php($editing = $book->exists)
<x-app-layout>
    @include('library._styles')
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $editing ? 'Edit Buku' : 'Tambah Buku' }}</h2>
    </x-slot>

    <div style="padding: 2rem 0;">
        <div class="lib-wrap lib-w-3xl">
            @include('library._flash')

            <form method="POST" action="{{ $editing ? route('books.update', $book) : route('books.store') }}" class="lib-card lib-form" enctype="multipart/form-data">
                @csrf
                @if ($editing) @method('PUT') @endif

                <div class="lib-section-title">Informasi Buku</div>
                <div>
                    <label for="title">Judul</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $book->title) }}" placeholder="Contoh: Pemrograman Web dengan Laravel" required>
                </div>
                <div class="lib-grid-2">
                    <div>
                        <label for="isbn">ISBN</label>
                        <input id="isbn" name="isbn" type="text" value="{{ old('isbn', (string) $book->isbn) }}" placeholder="978-0-306-40615-7" required>
                        <div class="lib-hint">ISBN-10 atau ISBN-13 (tanda hubung boleh dipakai)</div>
                    </div>
                    <div>
                        <label for="published_year">Tahun terbit</label>
                        <input id="published_year" name="published_year" type="number" value="{{ old('published_year', $book->published_year) }}" placeholder="2024">
                    </div>
                </div>
                <div class="lib-grid-2">
                    <div>
                        <label for="publisher_id">Penerbit</label>
                        <select id="publisher_id" name="publisher_id">
                            <option value="">- Pilih penerbit -</option>
                            @foreach ($publishers as $publisher)
                                <option value="{{ $publisher->id }}" @selected(old('publisher_id', $book->publisher_id) == $publisher->id)>{{ $publisher->name }}</option>
                            @endforeach
                        </select>
                        <div class="qa" data-url="{{ route('publishers.store') }}" data-target="publisher_id" data-single="1" data-label="Penerbit">
                            <button type="button" class="qa-toggle">＋ Penerbit baru</button>
                            <div class="qa-panel" hidden>
                                <input type="text" class="qa-name" maxlength="255" placeholder="Nama penerbit baru">
                                <input type="text" class="qa-extra" data-field="city" maxlength="100" placeholder="Kota (opsional)">
                                <div class="qa-actions">
                                    <button type="button" class="btn btn-primary btn-sm qa-save">Simpan penerbit</button>
                                    <button type="button" class="btn btn-ghost btn-sm qa-cancel">Batal</button>
                                </div>
                                <div class="qa-msg" role="alert"></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="total_stock">Total stok</label>
                        <input id="total_stock" name="total_stock" type="number" min="0" value="{{ old('total_stock', $book->total_stock ?? 1) }}" required>
                    </div>
                </div>

                <div class="lib-section-title" style="margin-top: 1.6rem;">Penulis &amp; Kategori</div>
                <div class="lib-grid-2">
                    <div>
                        <label for="author_ids">Penulis</label>
                        <select id="author_ids" name="author_ids[]" multiple size="6">
                            @foreach ($authors as $author)
                                <option value="{{ $author->id }}" @selected(in_array($author->id, old('author_ids', $book->exists ? $book->authors->pluck('id')->all() : [])))>{{ $author->name }}</option>
                            @endforeach
                        </select>
                        <div class="lib-hint">Tahan Ctrl/Cmd untuk memilih lebih dari satu</div>
                        <div class="qa" data-url="{{ route('authors.store') }}" data-target="author_ids" data-label="Penulis">
                            <button type="button" class="qa-toggle">＋ Penulis baru</button>
                            <div class="qa-panel" hidden>
                                <input type="text" class="qa-name" maxlength="255" placeholder="Nama penulis baru">
                                <div class="qa-actions">
                                    <button type="button" class="btn btn-primary btn-sm qa-save">Simpan penulis</button>
                                    <button type="button" class="btn btn-ghost btn-sm qa-cancel">Batal</button>
                                </div>
                                <div class="qa-msg" role="alert"></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label for="category_ids">Kategori</label>
                        <select id="category_ids" name="category_ids[]" multiple size="6">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(in_array($category->id, old('category_ids', $book->exists ? $book->categories->pluck('id')->all() : [])))>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <div class="lib-hint">Tahan Ctrl/Cmd untuk memilih lebih dari satu</div>
                        <div class="qa" data-url="{{ route('categories.store') }}" data-target="category_ids" data-label="Kategori">
                            <button type="button" class="qa-toggle">＋ Kategori baru</button>
                            <div class="qa-panel" hidden>
                                <input type="text" class="qa-name" maxlength="100" placeholder="Nama kategori baru">
                                <select class="qa-extra" data-field="parent_id" aria-label="Kategori induk">
                                    <option value="">Kategori utama (tanpa induk)</option>
                                    @foreach ($categories->whereNull('parent_id') as $root)
                                        <option value="{{ $root->id }}">Sub dari: {{ $root->name }}</option>
                                    @endforeach
                                </select>
                                <div class="qa-actions">
                                    <button type="button" class="btn btn-primary btn-sm qa-save">Simpan kategori</button>
                                    <button type="button" class="btn btn-ghost btn-sm qa-cancel">Batal</button>
                                </div>
                                <div class="qa-msg" role="alert"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" rows="4" placeholder="Ringkasan singkat isi buku (opsional)">{{ old('description', $book->description) }}</textarea>
                </div>

                <div>
                    <label for="cover">Sampul buku</label>
                    @if ($editing && $book->cover_url)
                        <div class="lib-cover-preview">
                            <img src="{{ $book->cover_url }}" alt="Sampul {{ $book->title }}">
                            <label class="lib-check"><input type="checkbox" name="remove_cover" value="1"> Hapus sampul saat ini</label>
                        </div>
                    @endif
                    <input id="cover" name="cover" type="file" accept="image/png,image/jpeg,image/webp">
                    <div class="lib-hint">JPG, PNG, atau WEBP, maksimal 2 MB. Disarankan rasio potret (3:4). Jika kosong, sampul otomatis dibuat dari judul buku.</div>
                </div>

                <div class="lib-form-actions">
                    <button type="submit" class="lib-btn lib-btn-primary">💾 Simpan</button>
                    <a href="{{ route('books.index') }}" class="lib-btn lib-btn-ghost">Batal</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        var token = document.querySelector('meta[name="csrf-token"]').content;

        document.querySelectorAll('.qa').forEach(function (box) {
            var panel = box.querySelector('.qa-panel'), msg = box.querySelector('.qa-msg'), name = box.querySelector('.qa-name');
            var extra = box.querySelector('.qa-extra'), target = document.getElementById(box.dataset.target), label = box.dataset.label;
            var save = box.querySelector('.qa-save');
            var flash = document.createElement('div'); flash.className = 'qa-flash'; box.appendChild(flash);

            var say = function (text, kind) { msg.textContent = text || ''; msg.className = 'qa-msg ' + (kind || ''); };
            box.querySelector('.qa-toggle').addEventListener('click', function () { panel.hidden = !panel.hidden; flash.textContent = ''; if (!panel.hidden) name.focus(); });
            box.querySelector('.qa-cancel').addEventListener('click', function () { panel.hidden = true; say(''); });
            name.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); save.click(); } });

            save.addEventListener('click', function () {
                var body = { name: name.value.trim() };
                if (extra && extra.value !== '') body[extra.dataset.field] = extra.value;
                if (! body.name) { say('Nama ' + label.toLowerCase() + ' wajib diisi.', 'err'); return; }

                save.disabled = true; say('Menyimpan...');
                fetch(box.dataset.url, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(body)
                }).then(function (res) { return res.json().then(function (data) { return { ok: res.ok, status: res.status, data: data }; }); })
                .then(function (r) {
                    if (r.ok) {
                        var text = r.data.name;
                        if (extra && extra.tagName === 'SELECT' && extra.value !== '') text = extra.options[extra.selectedIndex].text.replace('Sub dari: ', '') + ' › ' + text;
                        var opt = new Option(text, r.data.id, true, true);
                        if (box.dataset.single) target.value = ''; 
                        target.appendChild(opt); opt.selected = true;
                        name.value = ''; if (extra) extra.value = ''; panel.hidden = true; say('');
                        flash.textContent = '✓ ' + label + ' "' + r.data.name + '" ditambahkan dan langsung dipilih.';
                    } else if (r.status === 422) {
                        var first = r.data.errors ? Object.values(r.data.errors)[0][0] : r.data.message; say(first, 'err');
                    } else { say('Gagal menyimpan (' + r.status + '). Muat ulang halaman lalu coba lagi.', 'err'); }
                }).catch(function () { say('Tidak dapat menghubungi server.', 'err'); })
                .finally(function () { save.disabled = false; });
            });
        });
    })();
    </script>
</x-app-layout>
