<?php

namespace Modules\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Laporan stok habis: hanya pustakawan yang boleh melapor. */
class StoreBookStockReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPustakawan() ?? false;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.string' => 'Catatan laporan harus berupa teks.',
            'note.max' => 'Catatan laporan maksimal 255 karakter.',
        ];
    }
}
