<?php

namespace Modules\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Catalog\Models\Book;
use Modules\Catalog\ValueObjects\Isbn;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'isbn' => [
                'required', 'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (! Isbn::isValid((string) $value)) {
                        $fail('Format ISBN tidak valid (harus ISBN-10 atau ISBN-13 dengan check digit yang benar).');
                    } elseif (Book::withTrashed()
                        ->where('isbn', Isbn::normalize((string) $value))
                        ->when($this->route('book'), fn ($q, $b) => $q->where('id', '!=', is_object($b) ? $b->id : $b))
                        ->exists()) {
                        $fail('ISBN sudah terdaftar.');
                    }
                },
            ],
            'publisher_id' => ['nullable', 'exists:publishers,id'],
            'published_year' => ['nullable', 'integer', 'between:1000,'.date('Y')],
            'total_stock' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_cover' => ['nullable', 'boolean'],
            'author_ids' => ['required', 'array', 'min:1'],
            'author_ids.*' => ['exists:authors,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['exists:book_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul buku wajib diisi.',
            'title.max' => 'Judul buku maksimal 255 karakter.',
            'isbn.required' => 'ISBN wajib diisi.',
            'publisher_id.exists' => 'Penerbit yang dipilih tidak ditemukan.',
            'published_year.integer' => 'Tahun terbit harus berupa angka.',
            'published_year.between' => 'Tahun terbit harus antara 1000 dan tahun ini.',
            'total_stock.required' => 'Total stok wajib diisi.',
            'total_stock.integer' => 'Total stok harus berupa angka bulat.',
            'total_stock.min' => 'Total stok tidak boleh negatif.',
            'cover.image' => 'Sampul harus berupa gambar.',
            'cover.mimes' => 'Sampul harus berformat JPG, PNG, atau WEBP.',
            'cover.max' => 'Ukuran sampul maksimal 2 MB.',
            'author_ids.required' => 'Pilih minimal satu penulis.',
            'author_ids.min' => 'Pilih minimal satu penulis.',
            'author_ids.*.exists' => 'Penulis yang dipilih tidak ditemukan.',
            'category_ids.*.exists' => 'Kategori yang dipilih tidak ditemukan.',
        ];
    }
}
