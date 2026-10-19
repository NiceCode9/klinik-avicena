<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanPoliRequest extends FormRequest
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
                'max:5',
                Rule::unique('mtr_poli', 'kode')->ignore($this->route('poli')),
            ],
            'nama' => ['required', 'string', 'max:100'],
            'ihs_location_id' => ['nullable', 'string', 'max:64'],
            'aktif' => ['boolean'],
        ];
    }
}
