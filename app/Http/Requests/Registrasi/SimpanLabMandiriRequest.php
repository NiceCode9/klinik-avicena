<?php

namespace App\Http\Requests\Registrasi;

use Illuminate\Foundation\Http\FormRequest;

class SimpanLabMandiriRequest extends FormRequest
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
            'pasien_id' => ['nullable', 'integer', 'exists:mtr_pasien,id'],
            'nik' => ['required_without:pasien_id', 'nullable', 'string', 'regex:/^\d{16}$/'],
            'nama_lengkap' => ['required_without:pasien_id', 'nullable', 'string', 'max:150'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['required_without:pasien_id', 'nullable', 'date'],
            'jenis_kelamin' => ['required_without:pasien_id', 'nullable', 'in:L,P'],
            'golongan_darah' => ['nullable', 'in:A,B,AB,O,-'],
            'alamat' => ['nullable', 'string'],
            'no_telepon' => ['nullable', 'string', 'max:20'],
            'nama_wali' => ['nullable', 'string', 'max:150'],
            'dokter_id' => ['required', 'integer', 'exists:mtr_dokter,id'],
            'pemeriksaan_lab_ids' => ['required', 'array', 'min:1'],
            'pemeriksaan_lab_ids.*' => ['integer', 'exists:mtr_pemeriksaan_lab,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.regex' => 'NIK harus 16 digit angka.',
            'pemeriksaan_lab_ids.required' => 'Minimal satu pemeriksaan lab wajib dipilih.',
            'dokter_id.required' => 'Dokter lab wajib dipilih.',
        ];
    }
}
