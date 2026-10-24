<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class SimpanKunjunganRequest extends FormRequest
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
            'pasien_id' => ['required', 'exists:mtr_pasien,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pasien_id.required' => 'Pasien wajib dipilih.',
            'pasien_id.exists' => 'Pasien tidak ditemukan.',
        ];
    }
}
