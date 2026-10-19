<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class SimpanSupplierRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:150'],
            'alamat' => ['nullable', 'string'],
            'no_telepon' => ['nullable', 'string', 'max:20'],
            'nama_kontak' => ['nullable', 'string', 'max:100'],
            'no_izin_pbf' => ['nullable', 'string', 'max:50'],
            'aktif' => ['boolean'],
        ];
    }
}
