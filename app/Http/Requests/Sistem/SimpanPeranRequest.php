<?php

namespace App\Http\Requests\Sistem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanPeranRequest extends FormRequest
{
    /**
     * Izin dicek lewat middleware `izin:sistem.peran,<aksi>`.
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
            'nama' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sys_peran', 'nama')->ignore($this->route('peran')),
            ],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
