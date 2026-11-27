# Modul Circulation
 
Bounded context yang mengelola **sirkulasi buku**: peminjaman, pengajuan peminjaman, pengembalian, dan denda.
 
## Tanggung jawab
 
- Admin dan pustakawan mencatat peminjaman, menyetujui atau menolak pengajuan, dan memproses pengembalian.
- User (anggota) mengajukan pinjaman dan melihat halaman **Pinjaman Saya**.
- Admin dapat menghapus (soft delete) dan memulihkan data peminjaman.
 
## Model dan relasi
 
| Model | Keterangan |
|---|---|
| `Loan` | Transaksi peminjaman: nomor, anggota, tanggal pinjam, jatuh tempo, status, total denda. |
| `LoanDetail` | Baris buku pada satu peminjaman: judul, ISBN, jumlah, tanggal kembali, denda. |
 
Relasi One-to-Many: satu `Loan` memiliki banyak `LoanDetail` (`Loan::details`).
 
Data anggota dan buku disimpan sebagai **snapshot** (`member_name`, `book_title`, `book_isbn`), sehingga modul ini tidak membutuhkan relasi Eloquent ke modul lain.
 
## Alur peminjaman (`LoanService::borrow`)
 
Seluruh proses berjalan dalam satu `DB::transaction()`:
 
1. Anggota diperiksa lewat `MemberServiceInterface` (ditangguhkan atau melewati batas pinjam akan melempar `MemberCannotBorrowException`).
2. Record `Loan` dan `LoanDetail` dibuat.
3. Stok dikurangi lewat `BookServiceInterface`. Stok kurang melempar `BookNotAvailableException` dan semua perubahan di-rollback.
4. Status dan jumlah pinjaman aktif anggota diperbarui.
5. Setelah transaction berhasil di-commit, Domain Event `BookBorrowed` ditembakkan.
 
## Pengembalian dan denda
 
`LoanService::returnLoan` menghitung denda dengan Value Object `Denda`:
 
- Tarif **Rp1.000 per hari** keterlambatan.
- Keterlambatan dibulatkan ke atas per hari, dan 0 bila tepat waktu.
- Denda = jumlah hari x tarif x jumlah buku.
 
Stok buku bertambah lagi lewat `BookServiceInterface::increaseStock`, dan status anggota diperbarui lewat `MemberServiceInterface::markReturned`.
 
## Pengajuan oleh user
 
`LoanService::submitRequest` hanya mencatat pengajuan berstatus pending. Stok dan jatuh tempo baru berubah saat pustakawan menyetujui. Pengajuan yang masih menunggu ikut dihitung terhadap batas pinjam anggota.
 
## Domain Event
 
`BookBorrowed` ditangani oleh listener `LogBookBorrowed`, yang mencatat nomor peminjaman, anggota, judul buku, dan tanggal jatuh tempo ke log aplikasi.
 
## Hak akses (RBAC)
 
| Aksi | Admin | Pustakawan | User |
|---|---|---|---|
| Catat peminjaman, setujui, tolak, kembalikan | Ya | Ya | Tidak |
| Hapus dan restore peminjaman | Ya | Tidak | Tidak |
| Ajukan pinjaman dan lihat Pinjaman Saya | Tidak | Tidak | Ya |
 
## Komunikasi dengan modul lain
 
- Memakai `BookServiceInterface` (milik Catalog) dan `MemberServiceInterface` (milik Membership).
- `LoanQueryService` mengimplementasikan `LoanQueryInterface` agar Catalog dan Membership dapat memeriksa pinjaman aktif tanpa mengakses tabel Circulation.
 
## Struktur folder
 
- `Controllers/`, `Models/`, `Requests/`, `Services/`
- `Events/`, `Listeners/`, `ValueObjects/`
- `Migrations/` dan `routes/`
- `Providers/CirculationServiceProvider.php`: memuat migration dan route modul, serta mendaftarkan listener event
