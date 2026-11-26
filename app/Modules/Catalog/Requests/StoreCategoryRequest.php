<?php

namespace Modules\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** Slug dibuat otomatis dari nama kategori. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) $this->input('name')),
            'parent_id' => $this->input('parent_id') ?: null,
        ]);
    }

    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', Rule::unique('book_categories', 'slug')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            // Induk harus kategori utama (maksimal dua tingkat) dan bukan dirinya sendiri
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('book_categories', 'id')->whereNull('parent_id'),
                Rule::notIn([$category?->id]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'slug.required' => 'Nama kategori harus mengandung huruf atau angka.',
            'slug.unique' => 'Kategori dengan nama tersebut sudah ada.',
            'description.max' => 'Deskripsi maksimal 500 karakter.',
            'parent_id.exists' => 'Kategori induk harus berupa kategori utama.',
            'parent_id.not_in' => 'Kategori tidak boleh menjadi induk dirinya sendiri.',
        ];
    }
}
