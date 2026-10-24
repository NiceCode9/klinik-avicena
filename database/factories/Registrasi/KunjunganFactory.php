<?php

namespace Database\Factories\Registrasi;

use App\Models\Master\Pasien;
use App\Models\Registrasi\Kunjungan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Kunjungan>
 */
class KunjunganFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_kunjungan' => 'KJ-'.fake()->unique()->numerify('############'),
            'pasien_id' => Pasien::factory(),
            'tanggal_kunjungan' => today(),
            'jenis_kunjungan' => 'poli',
            'status' => 'terdaftar',
            'waktu_datang' => now(),
            'dibuat_oleh' => User::factory(),
        ];
    }
}
