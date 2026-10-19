<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class SimpanDokterRequest extends FormRequest
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
            'spesialisasi' => ['nullable', 'string', 'max:100'],
            'poli_id' => ['nullable', 'exists:mtr_poli,id'],
            'no_sip' => ['nullable', 'string', 'max:50'],
            'nik' => ['nullable', 'digits:16'],
            'ihs_practitioner_id' => ['nullable', 'string', 'max:64'],
            'tarif_konsultasi' => ['required', 'numeric', 'min:0'],
            'aktif' => ['boolean'],
        ];
    }
}
