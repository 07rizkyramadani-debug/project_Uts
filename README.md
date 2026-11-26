# Sistem Manajemen Perpustakaan Digital (Laravel 13 – Modular Monolith)

UTS Backend Development (DT199) – Universitas AMIKOM Yogyakarta. Studi kasus no. 1.

## Teknologi
Laravel 13 (PHP 8.4+), Modular Monolith, MySQL / PostgreSQL / SQLite, Laravel Sanctum (sesi web + token API), RBAC (admin / pustakawan / user), Blade, Pest, Pint, Git + GitHub.

## Struktur Modul (`app/Modules/`)
| Modul | Isi |
|---|---|
| `Auth` | Middleware `role`, API token Sanctum (kedaluwarsa otomatis), rate limiter |
| `Catalog` | Buku, Penulis, Kategori (dua tingkat), Penerbit, ISBN (Value Object) |
| `Membership` | Anggota (batas pinjam sama untuk semua: 5 buku, 14 hari, tanpa masa berlaku), tautan ke akun user |
| `Circulation` | Peminjaman, pengajuan & persetujuan, pengembalian, denda, event `BookBorrowed` |
| `Shared` | Contract (interface) dan Custom Exception antar modul |

Komunikasi antar modul hanya lewat `Modules\Shared\Contracts` dan Domain Event:
`BookServiceInterface` (Catalog), `MemberServiceInterface` (Membership), `LoanQueryInterface` (Circulation, dipakai untuk mencegah penghapusan buku/anggota yang masih terikat peminjaman).

## Instalasi
```bash
composer install
composer update laravel/sanctum        # jika Sanctum belum terpasang di vendor
cp .env.example .env && php artisan key:generate
# atur DB_* di .env (default SQLite: buat file database/database.sqlite)
php artisan migrate:fresh --seed
npm install && npm run build
php artisan serve
```
Jangan commit berkas `.env` ke GitHub.

## Akun contoh (seeder)
| Peran | Email | Password |
|---|---|---|
| Admin | admin@example.com | password123 |
| Pustakawan | pustakawan@example.com | password123 |
| User | user@example.com | password123 (sudah tertaut ke satu anggota) |

## Hak akses (RBAC)
| Fitur | Admin | Pustakawan | User |
|---|---|---|---|
| Lihat katalog, penulis, kategori | Ya | Ya | Ya |
| CRUD buku, penulis, kategori, penerbit, pulihkan data terhapus | Ya | Tidak | Tidak |
| Tambah anggota & lihat daftar anggota | Ya | Ya | Tidak |
| Ubah / hapus anggota | Ya | Tidak | Tidak |
| Catat peminjaman, pengembalian | Ya | Ya | Tidak |
| Laporkan buku yang stoknya habis ke admin | Tidak | Ya | Tidak |
| Lihat & tutup laporan stok habis | Ya | Tidak | Tidak |
| Setujui / tolak pengajuan peminjaman | Ya | Ya | Tidak |
| Hapus / pulihkan peminjaman | Ya | Tidak | Tidak |
| Ajukan peminjaman & lihat "Pinjaman Saya" | Tidak | Tidak | Ya (jika akun tertaut ke anggota) |

## Alur pengajuan peminjaman
1. Admin/pustakawan menautkan akun user ke data anggota (form anggota).
2. User memilih buku di katalog lalu **Ajukan Pinjam** (status `pending`, stok belum berubah).
3. Pustakawan **menyetujui** (stok berkurang, jatuh tempo dihitung) atau **menolak** (dengan alasan).
4. Pustakawan mencatat **pengembalian**; denda keterlambatan dihitung otomatis.

Buku atau anggota yang masih punya peminjaman berjalan / pengajuan menunggu **tidak dapat dihapus**
(`ResourceInUseException`). Penulis, kategori, dan penerbit yang masih dipakai juga tidak dapat dihapus.

## API (Sanctum token)
`POST /api/register`, `POST /api/login`, `GET /api/me`, `POST /api/logout`, `GET /api/books`, `POST /api/loans`, `POST /api/loans/{id}/return`.
- Rate limit: login/register 5 per menit, endpoint lain 60 per menit.
- Token kedaluwarsa setelah `SANCTUM_TOKEN_EXPIRATION` menit (default 1440).
- CORS diatur di `config/cors.php` (`CORS_ALLOWED_ORIGINS`).

## Pengujian
```bash
php artisan test        # 128 test (Pest), termasuk arch test batas modul
vendor/bin/pint         # code style
```

## Sampul buku
Gambar sampul disajikan lewat route `book-covers/{file}` (`BookCoverController`), sehingga tampil tanpa `php artisan storage:link` dan tidak bergantung pada nilai `APP_URL`. Sampul bawaan ada di `database/seeders/covers` dan disalin ke `storage/app/public/covers` saat `php artisan migrate:fresh --seed`. Bila gambar gagal dimuat, tampil sampul otomatis (gradien + judul).

## Halaman awal (landing page)
Alamat `/` menampilkan halaman awal publik (`WelcomeController`, `resources/views/landing.blade.php`, `public/css/landing.css`) berisi pengenalan sistem, contoh sampul koleksi, statistik, fitur, cara kerja, FAQ, serta tombol Masuk dan Daftar. Pengguna yang sudah login langsung diarahkan ke dashboard. Halaman ini tidak memerlukan build Vite.

## Dashboard & riwayat peminjaman
- **User**: sapaan sesuai waktu, ringkasan *Total Buku Dipinjam*, *Sedang Dipinjam*, pengajuan, dan denda; kuota pinjam; daftar peminjaman berjalan; riwayat terbaru; rekomendasi buku tersedia.
- **Pustakawan**: statistik operasional, grafik aktivitas 7 hari, komposisi status peminjaman, pengajuan yang perlu ditinjau, peminjaman terlambat, peminjaman terbaru, buku terpopuler, dan pintasan.
- **Admin**: seluruh tampilan pustakawan ditambah pintasan data master dan jumlah pengguna per peran; halaman *Dashboard Admin* berisi komposisi pengguna dan pengguna terbaru.
- **Pinjaman Saya** (user): ringkasan, kartu peminjaman berjalan, dan **Riwayat peminjaman** (dikembalikan/ditolak, 8 per halaman).
- Sampul pada daftar pinjaman diambil lewat contract `BookServiceInterface::getCoverMap()` sehingga modul Circulation tidak mengakses Catalog secara langsung.

## Daftar buku, anggota, dan peminjaman (kartu)
- **Daftar buku**: semua peran memakai tampilan kartu bersampul. Aksi berbeda per peran: admin mendapat **Edit** dan **Hapus** (beserta info penerbit, ISBN, dan penanda stok yang dilaporkan pustakawan); pustakawan **Laporkan stok**; user **Ajukan Pinjam**.
- **Tambah buku (admin)**: penerbit, penulis, dan kategori baru bisa dibuat langsung dari form lewat tombol "＋ ... baru" (AJAX ke `POST /publishers`, `/authors`, `/categories` yang mengembalikan JSON `201`), lalu otomatis terpilih. Pesan validasi (misalnya nama kembar) tampil di bawah form kecil.
- **Anggota** (admin & pustakawan): kartu anggota dengan status, kuota pinjam aktif, dan tautan akun. Admin: Edit/Hapus. Pustakawan: **Pinjamkan Buku** (membuka form peminjaman dengan anggota terpilih).
- **Peminjaman** (admin & pustakawan): kartu dengan sampul, anggota, jatuh tempo, dan denda; tab status menampilkan jumlahnya; pengajuan menunggu selalu di urutan teratas. Aksi: Setujui/Tolak, Kembalikan, Detail; Hapus hanya untuk admin.

## Data Master & Laporan Stok (admin)
- **Data Master** (Penulis, Kategori, Penerbit) berbentuk kartu tanpa tabel: tab menampilkan jumlah data, ada ringkasan, pencarian (penulis & penerbit), dan aksi Edit/Hapus. Kategori tampil sebagai kartu kelompok berisi sub-kategori dengan tombol **Tambah sub-kategori** (induk terpilih otomatis).
- **Laporan Stok**: kartu per laporan (sampul, penulis, stok saat ini, pelapor, catatan) dengan tab *Semua / Menunggu / Selesai* beserta jumlahnya; aksi **Tambah Stok** dan **Tandai Selesai**.
- Kedua halaman memakai sistem animasi yang sama (`motion.css`/`motion.js`): kartu masuk berurutan, sub-kategori dan chip muncul bertahap, badge stok 0 bergoyang halus, laporan menunggu berpendar lembut.
