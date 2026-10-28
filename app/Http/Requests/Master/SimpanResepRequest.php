<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class SimpanResepRequest extends FormRequest
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
            'items' => ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pemeriksaan_id.required' => 'Pemeriksaan wajib dipilih.',
            'pemeriksaan_id.exists' => 'Pemeriksaan tidak ditemukan.',
        ];
    }
}
