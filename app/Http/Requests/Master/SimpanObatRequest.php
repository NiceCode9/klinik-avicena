<?php

namespace App\Http\Requests\Master;

use App\Models\Master\Obat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanObatRequest extends FormRequest
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
            // Unik mengikuti constraint DB: kode milik baris soft-deleted tetap terpakai.
            'kode' => [
                'required',
                'string',
                'max:30',
                Rule::unique('mtr_obat', 'kode')->ignore($this->route('obat')),
            ],
            'nama' => ['required', 'string', 'max:200'],
            'kategori_obat_id' => ['nullable', 'exists:mtr_kategori_obat,id'],
            'satuan_id' => ['required', 'exists:mtr_satuan,id'],
            'golongan' => ['required', Rule::in(array_keys(Obat::GOLONGAN))],
            'stok_minimum' => ['required', 'numeric', 'min:0'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'kode_kfa' => ['nullable', 'string', 'max:50'],
            'aktif' => ['boolean'],
        ];
    }
}
