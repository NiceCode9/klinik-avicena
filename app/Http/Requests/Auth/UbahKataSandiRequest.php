<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UbahKataSandiRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'kata_sandi_lama' => ['required', 'current_password'],
            'kata_sandi' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kata_sandi_lama.current_password' => 'Kata sandi lama tidak cocok.',
            'kata_sandi.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ];
    }
}
