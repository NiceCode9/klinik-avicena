<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanSatuanRequest extends FormRequest
{
    /**
     * Izin dicek lewat middleware `izin:master.satuan,<aksi>`.
     */
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
            // Unik mengikuti constraint DB: nama milik baris soft-deleted tetap terpakai.
            'nama' => [
                'required',
                'string',
                'max:50',
                Rule::unique('mtr_satuan', 'nama')->ignore($this->route('satuan')),
            ],
        ];
    }
}
