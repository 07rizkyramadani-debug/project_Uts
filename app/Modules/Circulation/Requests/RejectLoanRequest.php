<?php

namespace Modules\Circulation\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Penolakan pengajuan peminjaman oleh admin atau pustakawan, dengan alasan opsional. */
class RejectLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageCirculation() ?? false;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.string' => 'Alasan penolakan harus berupa teks.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 255 karakter.',
        ];
    }
}
