<?php

namespace App\Http\Requests\Sistem;

use App\Models\Sys\Pengaturan;
use Illuminate\Foundation\Http\FormRequest;

class SimpanPengaturanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan dibuat per kunci yang ada di DB, sehingga kunci asing ikut terabaikan.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $aturan = ['nilai' => ['array']];

        foreach (Pengaturan::orderBy('kunci')->pluck('kunci') as $kunci) {
            $aturan["nilai.{$kunci}"] = match (Pengaturan::TIPE[$kunci] ?? 'teks') {
                'uang' => ['nullable', 'numeric', 'min:0'],
                'boolean' => ['nullable', 'in:0,1'],
                'angka' => ['nullable', 'integer', 'min:0'],
                default => ['nullable', 'string', 'max:1000'],
            };
        }

        return $aturan;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nilai.biaya_jasa_racik' => 'Biaya Jasa Racik',
            'nilai.retur_pasien_diizinkan' => 'Retur Pasien',
            'nilai.batas_peringatan_kedaluwarsa_hari' => 'Batas Peringatan Kedaluwarsa',
        ];
    }
}
