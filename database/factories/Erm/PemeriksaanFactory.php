<?php

namespace Database\Factories\Erm;

use App\Models\Erm\Pemeriksaan;
use App\Models\Master\Dokter;
use App\Models\Master\Poli;
use App\Models\Registrasi\Kunjungan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pemeriksaan>
 */
class PemeriksaanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kunjungan_id' => Kunjungan::factory(),
            'antrean_id' => null,
            'poli_id' => Poli::factory(),
            'dokter_id' => Dokter::factory(),
            'dikunci' => false,
        ];
    }
}
