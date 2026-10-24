<?php

namespace Database\Factories\Erm;

use App\Models\Erm\Pemeriksaan;
use App\Models\Erm\Resep;
use App\Models\Master\Dokter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resep>
 */
class ResepFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pemeriksaan_id' => Pemeriksaan::factory(),
            'dokter_id' => Dokter::factory(),
            'no_resep' => 'RES-'.fake()->unique()->numerify('######'),
            'status' => 'diterbitkan',
        ];
    }
}
