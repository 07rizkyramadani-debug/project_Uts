<?php

namespace Modules\Shared\Contracts;

/**
 * Contract Circulation Context -> dipakai oleh Catalog dan Membership.
 * Dipakai untuk memastikan buku/anggota tidak dihapus selama masih terikat peminjaman,
 * tanpa Catalog/Membership mengakses model atau tabel Circulation secara langsung.
 */
interface LoanQueryInterface
{
    /** Apakah buku masih ada di peminjaman yang berjalan atau pengajuan yang menunggu persetujuan. */
    public function hasActiveLoansForBook(int $bookId): bool;

    /** Apakah anggota masih punya peminjaman yang berjalan atau pengajuan yang menunggu persetujuan. */
    public function hasActiveLoansForMember(int $memberId): bool;
}
