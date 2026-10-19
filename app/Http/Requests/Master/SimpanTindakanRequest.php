<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanTindakanRequest extends FormRequest
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
                Rule::unique('mtr_tindakan', 'kode')->ignore($this->route('tindakan')),
            ],
            'nama' => ['required', 'string', 'max:150'],
            'poli_id' => ['nullable', 'exists:mtr_poli,id'],
            'tarif' => ['required', 'numeric', 'min:0'],
            'aktif' => ['boolean'],
        ];
    }
}
