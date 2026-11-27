# Shared Kernel
 
Berisi hal-hal yang dipakai bersama oleh seluruh modul. Folder ini **tidak berisi logika bisnis** milik modul tertentu, hanya kontrak dan tipe umum.
 
## Contract (interface)
 
Komunikasi antar modul hanya boleh lewat Contract ini, bukan lewat model atau tabel modul lain.
 
| Contract | Diimplementasikan oleh | Dipakai oleh | Tujuan |
|---|---|---|---|
| `BookServiceInterface` | Catalog (`BookService`) | Circulation | Cek ketersediaan dan ubah stok buku. |
| `MemberServiceInterface` | Membership (`MemberService`) | Circulation, Auth | Validasi anggota, batas pinjam, status anggota, aktivasi saat registrasi. |
| `LoanQueryInterface` | Circulation (`LoanQueryService`) | Catalog, Membership | Mencegah penghapusan buku atau anggota yang masih memiliki pinjaman aktif. |
 
Binding interface ke implementasinya didaftarkan di `AppServiceProvider`.
 
## Custom Exception
 
| Exception | Dilempar saat | Respons API |
|---|---|---|
| `BookNotAvailableException` | Stok buku tidak cukup. | JSON 422, kunci `book_not_available` |
| `MemberCannotBorrowException` | Anggota ditangguhkan atau melewati batas pinjam. | JSON 422, kunci `member_cannot_borrow` |
| `ResourceInUseException` | Data yang masih dipakai data lain akan dihapus. | JSON 422, kunci `resource_in_use` |
 
Setiap exception memiliki metode `render()`. Untuk permintaan web, pengguna dikembalikan ke form dengan pesan error yang informatif. Untuk permintaan API, respons berupa JSON.
 
## Value Object
 
`Money` menyimpan nominal uang bersama mata uangnya (default IDR) dan tidak dapat bernilai negatif.
 
## Aturan batas modul
 
Modul Catalog, Membership, dan Circulation tidak boleh memakai namespace modul lain. Aturan ini diuji otomatis oleh `tests/Feature/Architecture/ModuleBoundaryTest.php`.
