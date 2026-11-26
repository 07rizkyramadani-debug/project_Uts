<?php

namespace Modules\Circulation\Requests;

use Illuminate\Support\Arr;

/**
 * Pengajuan peminjaman oleh user. Anggota ditentukan dari akun yang login (bukan dari input),
 * dan lama pinjam mengikuti role anggota, jadi hanya daftar buku dan catatan yang diterima.
 */
class SubmitLoanRequest extends StoreLoanRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isUser() ?? false;
    }

    public function rules(): array
    {
        return Arr::except(parent::rules(), ['member_id', 'loan_days']);
    }
}
