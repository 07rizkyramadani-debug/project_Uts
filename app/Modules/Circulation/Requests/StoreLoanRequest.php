<?php

namespace Modules\Circulation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageCirculation() ?? false;
    }

    /** Baris buku yang dikosongkan pada form (opsional) dibuang sebelum divalidasi. */
    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))
            ->filter(fn ($item) => is_array($item) && filled($item['book_id'] ?? null))
            ->values()
            ->all();

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.book_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:5'],
            'loan_days' => ['nullable', 'integer', 'between:1,30'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.required' => 'Anggota wajib dipilih.',
            'items.required' => 'Pilih minimal satu buku untuk dipinjam.',
            'items.min' => 'Pilih minimal satu buku untuk dipinjam.',
            'items.*.book_id.required' => 'Buku wajib dipilih.',
            'items.*.book_id.distinct' => 'Buku yang sama tidak boleh dipilih dua kali.',
            'items.*.quantity.max' => 'Jumlah per buku maksimal 5 eksemplar.',
            'items.*.quantity.min' => 'Jumlah minimal 1 eksemplar.',
            'loan_days.between' => 'Lama pinjam harus antara 1 sampai 30 hari.',
        ];
    }
}
