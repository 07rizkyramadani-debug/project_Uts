<?php

namespace Modules\Membership\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMembershipTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $type = $this->route('membership_type');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('membership_types', 'name')->ignore($type?->id)],
            'max_loans' => ['required', 'integer', 'between:1,20'],
            'loan_days' => ['required', 'integer', 'between:1,30'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama jenis keanggotaan wajib diisi.',
            'name.unique' => 'Jenis keanggotaan dengan nama tersebut sudah ada.',
            'max_loans.required' => 'Batas pinjam wajib diisi.',
            'max_loans.integer' => 'Batas pinjam harus berupa angka.',
            'max_loans.between' => 'Batas pinjam harus antara 1 sampai 20 buku.',
            'loan_days.required' => 'Lama pinjam wajib diisi.',
            'loan_days.integer' => 'Lama pinjam harus berupa angka.',
            'loan_days.between' => 'Lama pinjam harus antara 1 sampai 30 hari.',
        ];
    }
}

