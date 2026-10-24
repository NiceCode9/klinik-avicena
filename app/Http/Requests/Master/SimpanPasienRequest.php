<?php

namespace App\Http\Requests\Master;

use App\Models\Master\Pasien;
use App\Services\NikHasher;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanPasienRequest extends FormRequest
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
        $pasien = $this->route('pasien');
        $pasienId = $pasien instanceof Pasien ? $pasien->id : null;

        return [
            'nik' => [
                'required',
                'digits:16',
                function (string $attribute, mixed $value, Closure $fail) use ($pasienId) {
                    $hash = NikHasher::hash((string) $value);

                    $duplikat = $hash !== null && Pasien::withTrashed()
                        ->where('nik_hash', $hash)
                        ->when($pasienId, fn ($query) => $query->whereKeyNot($pasienId))
                        ->exists();

                    if ($duplikat) {
                        $fail('NIK sudah terdaftar pada pasien lain.');
                    }
                },
            ],
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'jenis_kelamin' => ['required', Rule::in(['L', 'P'])],
            'golongan_darah' => ['nullable', Rule::in(['A', 'B', 'AB', 'O', '-'])],
            'alamat' => ['nullable', 'string', 'max:255'],
            'no_telepon' => ['nullable', 'string', 'max:20'],
            'nama_wali' => ['nullable', 'string', 'max:150'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nik.required' => 'NIK wajib diisi.',
            'nik.digits' => 'NIK harus 16 digit angka.',
            'nama_lengkap.required' => 'Nama lengkap wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
            'tanggal_lahir.date' => 'Tanggal lahir tidak valid.',
        ];
    }
}
