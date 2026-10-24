<?php

namespace Database\Factories\Master;

use App\Models\Master\Dokter;
use App\Models\Master\Poli;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dokter>
 */
class DokterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'spesialisasi' => fake()->optional()->word(),
            'poli_id' => Poli::factory(),
            'no_sip' => fake()->optional()->numerify('SIP-#####'),
            'nik' => fake()->optional()->numerify('################'),
            'tarif_konsultasi' => fake()->randomFloat(2, 0, 500000),
            'aktif' => true,
        ];
    }
}
