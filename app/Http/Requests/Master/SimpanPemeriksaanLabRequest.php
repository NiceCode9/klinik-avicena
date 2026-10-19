<?php

namespace App\Http\Requests\Master;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanPemeriksaanLabRequest extends FormRequest
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
                'max:20',
                Rule::unique('mtr_pemeriksaan_lab', 'kode')->ignore($this->route('pemeriksaanLab')),
            ],
            'nama' => ['required', 'string', 'max:150'],
            'tarif' => ['required', 'numeric', 'min:0'],
            'aktif' => ['boolean'],
            'parameter' => ['array'],
            'parameter.*.id' => ['nullable', 'integer'],
            'parameter.*.nama_parameter' => ['nullable', 'string', 'max:150'],
            'parameter.*.satuan' => ['nullable', 'string', 'max:50'],
            'parameter.*.nilai_rujukan_min' => ['nullable', 'numeric'],
            'parameter.*.nilai_rujukan_max' => ['nullable', 'numeric'],
            'parameter.*.keterangan_rujukan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $nama = [];

            foreach ($this->barisParameter() as $posisi => $baris) {
                $namaBaris = trim((string) ($baris['nama_parameter'] ?? ''));
                if ($namaBaris !== '') {
                    $nama[] = $namaBaris;
                }

                $min = $baris['nilai_rujukan_min'] ?? null;
                $max = $baris['nilai_rujukan_max'] ?? null;

                if ($min !== null && $min !== '' && $max !== null && $max !== '' && (float) $max < (float) $min) {
                    $validator->errors()->add(
                        "parameter.{$posisi}.nilai_rujukan_max",
                        'Nilai rujukan maksimum tidak boleh lebih kecil dari minimum.',
                    );
                }
            }

            if ($nama === []) {
                $validator->errors()->add('parameter', 'Isi minimal satu parameter.');
            } elseif (count($nama) !== count(array_unique($nama))) {
                $validator->errors()->add('parameter', 'Nama parameter tidak boleh kembar dalam satu pemeriksaan.');
            }
        });
    }

    /**
     * @return array<int|string, mixed>
     */
    private function barisParameter(): array
    {
        $baris = $this->input('parameter', []);

        return is_array($baris) ? $baris : [];
    }
}
