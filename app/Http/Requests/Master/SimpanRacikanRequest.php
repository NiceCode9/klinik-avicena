<?php

namespace App\Http\Requests\Master;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanRacikanRequest extends FormRequest
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
            // Tabel belum punya constraint unik; keunikan nama dijaga di aplikasi.
            'nama_menu' => [
                'required',
                'string',
                'max:150',
                Rule::unique('mtr_racikan', 'nama_menu')->ignore($this->route('racikan')),
            ],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'aktif' => ['boolean'],
            'bahan' => ['array'],
            'bahan.*.id' => ['nullable', 'integer'],
            'bahan.*.obat_id' => ['nullable', 'integer', 'exists:mtr_obat,id'],
            // Jumlah & satuan wajib begitu obat diisi (kolomnya NOT NULL di DB).
            'bahan.*.jumlah' => ['required_with:bahan.*.obat_id', 'nullable', 'numeric', 'gt:0'],
            'bahan.*.satuan_id' => ['required_with:bahan.*.obat_id', 'nullable', 'integer', 'exists:mtr_satuan,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $obat = [];

            foreach ($this->barisBahan() as $baris) {
                $obatId = trim((string) ($baris['obat_id'] ?? ''));
                if ($obatId !== '') {
                    $obat[] = $obatId;
                }
            }

            if ($obat === []) {
                $validator->errors()->add('bahan', 'Isi minimal satu bahan.');
            } elseif (count($obat) !== count(array_unique($obat))) {
                $validator->errors()->add('bahan', 'Obat yang sama tidak boleh dobel dalam satu racikan.');
            }
        });
    }

    /**
     * @return array<int|string, mixed>
     */
    private function barisBahan(): array
    {
        $bahan = $this->input('bahan', []);

        return is_array($bahan) ? $bahan : [];
    }
}
