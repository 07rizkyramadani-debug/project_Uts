# Modul Membership
 
Bounded context yang mengelola **anggota perpustakaan** dan **jenis keanggotaan**.
 
## Tanggung jawab
 
- CRUD anggota (admin dan pustakawan dapat melihat dan menambah; ubah, hapus, dan restore khusus admin).
- CRUD jenis keanggotaan (khusus admin).
- Menentukan batas pinjam dan lama pinjam setiap anggota.
- Mengaktifkan anggota secara otomatis saat sebuah akun mendaftar.
 
## Model dan relasi
 
| Model | Keterangan |
|---|---|
| `Member` | Data anggota: nomor anggota, nama, email, status, jumlah pinjaman aktif. Memakai soft delete. |
| `MembershipType` | Jenis keanggotaan: nama, `max_loans`, `loan_days`. |
 
Relasi One-to-Many: satu `MembershipType` memiliki banyak `Member` (`MembershipType::members`, `Member::membershipType`).
 
## Jenis keanggotaan bawaan
 
| Jenis | Batas pinjam | Lama pinjam |
|---|---|---|
| Umum | 5 buku | 14 hari |
| Mahasiswa | 7 buku | 21 hari |
| Dosen | 10 buku | 30 hari |
 
Anggota baru tanpa jenis yang dipilih otomatis menjadi jenis Umum.
 
## Status anggota
 
- `active`: boleh meminjam.
- `borrowing`: sedang memiliki pinjaman aktif.
- `suspended`: ditangguhkan, tidak boleh meminjam.
 
Nomor anggota dibuat otomatis dengan format `AGT-<tahun bulan>-<kode unik>`.
 
## Aktivasi otomatis saat registrasi
 
Akun baru ber-role `user` langsung dibuatkan data anggota aktif lewat `MemberService::ensureMemberForUser()`. Bila sudah ada data anggota dengan email yang sama dan belum punya akun, data itu dipakai ulang.
 
## Komunikasi dengan modul lain
 
`MemberService` mengimplementasikan `MemberServiceInterface` (Shared Kernel). Modul Circulation memakainya untuk memeriksa anggota (`getBorrowBlockReason`), menaikkan jumlah pinjaman (`markBorrowing`), dan mengembalikannya (`markReturned`). Modul ini tidak mengakses model Circulation secara langsung. Pemeriksaan pinjaman aktif sebelum menghapus anggota dilakukan lewat `LoanQueryInterface`, dan bila masih ada pinjaman maka `ResourceInUseException` dilempar.
 
## Struktur folder
 
- `Controllers/`: `MemberController`, `MembershipTypeController`
- `Models/`: `Member`, `MembershipType`
- `Requests/`: `StoreMemberRequest`, `StoreMembershipTypeRequest`
- `Services/`: `MemberService`
- `Migrations/` dan `routes/web.php`
- `Providers/MembershipServiceProvider.php`: memuat migration dan route modul
