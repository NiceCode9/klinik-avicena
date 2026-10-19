<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanIcd10Request extends FormRequest
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
            'kode' => [
                'required',
                'string',
                'max:10',
                Rule::unique('mtr_icd10', 'kode')->ignore($this->route('icd10')),
            ],
            'nama_diagnosis' => ['required', 'string', 'max:255'],
        ];
    }
}
