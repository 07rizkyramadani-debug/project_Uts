<?php

namespace Modules\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublisherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $publisher = $this->route('publisher');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('publishers', 'name')->ignore($publisher?->id)],
            'city' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama penerbit wajib diisi.',
            'name.unique' => 'Penerbit dengan nama tersebut sudah ada.',
            'city.max' => 'Nama kota maksimal 100 karakter.',
        ];
    }
}
