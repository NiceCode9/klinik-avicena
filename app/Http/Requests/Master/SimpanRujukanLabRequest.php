<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class SimpanRujukanLabRequest extends FormRequest
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
            'pemeriksaan_id' => ['required', 'exists:erm_pemeriksaan,id'],
            'pemeriksaan_lab_ids' => ['required', 'array', 'min:1'],
            'pemeriksaan_lab_ids.*' => ['exists:mtr_pemeriksaan_lab,id'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pemeriksaan_id.required' => 'Pemeriksaan wajib dipilih.',
            'pemeriksaan_lab_ids.required' => 'Pilih minimal satu pemeriksaan lab.',
            'pemeriksaan_lab_ids.min' => 'Pilih minimal satu pemeriksaan lab.',
        ];
    }
}
