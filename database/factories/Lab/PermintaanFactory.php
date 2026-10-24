<?php

namespace Database\Factories\Lab;

use App\Models\Lab\Permintaan;
use App\Models\Registrasi\Kunjungan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permintaan>
 */
class PermintaanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_permintaan' => 'LAB-'.fake()->unique()->numerify('######'),
            'kunjungan_id' => Kunjungan::factory(),
            'antrean_id' => null,
            'sumber' => 'rujukan',
            'rujukan_lab_id' => null,
            'dokter_id' => null,
            'petugas_id' => null,
            'status' => 'menunggu',
            'dikunci' => false,
        ];
    }
}
