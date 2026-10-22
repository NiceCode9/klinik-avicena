<?php

namespace App\Http\Requests\Master;

use App\Models\Master\Nakes;
use App\Services\NikHasher;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanNakesRequest extends FormRequest
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
        $nakes = $this->route('nakes');
        $nakesId = $nakes instanceof Nakes ? $nakes->id : null;

        return [
            'nama' => ['required', 'string', 'max:150'],
            'jenis' => ['required', Rule::in(array_keys(Nakes::JENIS))],
            'nik' => [
                'nullable',
                'digits:16',
                function (string $attribute, mixed $value, Closure $fail) use ($nakesId) {
                    $hash = NikHasher::hash($value);

                    $duplikat = $hash !== null && Nakes::withTrashed()
                        ->where('nik_hash', $hash)
                        ->when($nakesId, fn ($query) => $query->whereKeyNot($nakesId))
                        ->exists();

                    if ($duplikat) {
                        $fail('NIK sudah terdaftar pada tenaga kesehatan lain.');
                    }
                },
            ],
            'no_str' => ['nullable', 'string', 'max:50'],
            'ihs_practitioner_id' => ['nullable', 'string', 'max:64'],
            'pengguna_id' => [
                'nullable',
                'exists:sys_pengguna,id',
                Rule::unique('mtr_nakes', 'pengguna_id')->ignore($nakesId),
            ],
            'aktif' => ['boolean'],
        ];
    }
}
