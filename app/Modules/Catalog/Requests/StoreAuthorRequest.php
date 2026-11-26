<?php

namespace Modules\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama penulis wajib diisi.',
            'name.max' => 'Nama penulis maksimal 255 karakter.',
            'bio.max' => 'Biografi maksimal 1000 karakter.',
        ];
    }
}
