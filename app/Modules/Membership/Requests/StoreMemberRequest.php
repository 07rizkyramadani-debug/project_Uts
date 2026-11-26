<?php

namespace Modules\Membership\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberRequest extends FormRequest
{
    /** Membuat anggota: admin & pustakawan. Mengubah anggota yang ada: hanya admin. */
    public function authorize(): bool
    {
        if ($this->route('member')) {
            return $this->user()?->isAdmin() ?? false;
        }

        return $this->user()?->canManageCirculation() ?? false;
    }

    public function rules(): array
    {
        $member = $this->route('member');
        $ignoreId = is_object($member) ? $member->id : $member;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('members', 'email')->ignore($ignoreId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'membership_type_id' => ['nullable', 'integer', Rule::exists('membership_types', 'id')],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama anggota wajib diisi.',
            'email.required' => 'Email anggota wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar pada anggota lain.',
            'phone.max' => 'Nomor telepon maksimal 30 karakter.',
            'membership_type_id.exists' => 'Jenis keanggotaan tidak ditemukan.',
            'status.in' => 'Status anggota tidak valid.',
        ];
    }
}
