# Dokumentasi API (Sanctum)

Semua request memakai header `Accept: application/json`.
Endpoint terlindungi memakai header `Authorization: Bearer <token>`.

| Method | URL | Akses | Keterangan |
|---|---|---|---|
| POST | /api/register | Publik (5/menit) | Daftar akun, menerima token |
| POST | /api/login | Publik (5/menit) | Login, menerima token |
| GET | /api/me | Token | Data akun yang sedang login |
| POST | /api/logout | Token | Mencabut token |
| GET | /api/books | Token | Daftar buku |
| POST | /api/loans | Token, admin/pustakawan | Mencatat peminjaman |
| POST | /api/loans/{id}/return | Token, admin/pustakawan | Pengembalian |

## Contoh login
Request: `POST /api/login` dengan body JSON `{"email": "user@example.com", "password": "password123"}`
Respons 200: berisi `user` dan `token`.

## Kode status
- 401: token tidak ada atau tidak valid
- 403: peran tidak berhak (contoh: user biasa memanggil POST /api/loans)
- 422: validasi gagal atau stok tidak cukup
- 429: terlalu banyak percobaan login