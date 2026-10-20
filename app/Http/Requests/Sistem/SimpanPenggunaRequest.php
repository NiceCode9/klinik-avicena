<?php

namespace App\Http\Requests\Sistem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanPenggunaRequest extends FormRequest
{
    /**
     * Izin dicek lewat middleware `izin:sistem.pengguna,<aksi>`.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $pengguna = $this->route('pengguna');

        return [
            'nama' => ['required', 'string', 'max:255'],
            // Unik mengikuti constraint DB: username/email milik baris soft-deleted tetap terpakai.
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sys_pengguna', 'username')->ignore($pengguna),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('sys_pengguna', 'email')->ignore($pengguna),
            ],
            // Kosong saat mengubah berarti kata sandi lama dipertahankan.
            'password' => [$pengguna ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'peran_id' => ['required', 'integer', Rule::exists('sys_peran', 'id')],
            'dokter_id' => ['nullable', 'integer', Rule::exists('mtr_dokter', 'id')],
            'aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'password' => 'kata sandi',
            'peran_id' => 'peran',
            'dokter_id' => 'dokter',
        ];
    }
}
