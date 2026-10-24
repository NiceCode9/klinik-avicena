<?php

namespace Database\Factories\Master;

use App\Models\Master\Pasien;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pasien>
 */
class PasienFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_rekam_medis' => fake()->unique()->numerify('RM-######'),
            'nik' => fake()->unique()->numerify('################'),
            'nama_lengkap' => fake()->name(),
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->date(),
            'jenis_kelamin' => fake()->randomElement(['L', 'P']),
            'golongan_darah' => fake()->randomElement(['A', 'B', 'AB', 'O', '-']),
            'alamat' => fake()->address(),
            'no_telepon' => fake()->numerify('08##########'),
            'nama_wali' => fake()->name(),
        ];
    }
}
