<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanKategoriObatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Unik mengikuti constraint DB: nama milik baris soft-deleted tetap terpakai.
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mtr_kategori_obat', 'nama')->ignore($this->route('kategoriObat')),
            ],
        ];
    }
}
