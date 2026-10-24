<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class SimpanTtvRequest extends FormRequest
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
            'poli_id' => ['required', 'exists:mtr_poli,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'poli_id.required' => 'Poli wajib dipilih.',
            'poli_id.exists' => 'Poli tidak ditemukan.',
        ];
    }
}
