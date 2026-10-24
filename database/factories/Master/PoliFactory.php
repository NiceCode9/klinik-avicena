<?php

namespace Database\Factories\Master;

use App\Models\Master\Poli;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Poli>
 */
class PoliFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kode' => Str::upper(fake()->unique()->lexify('???')),
            'nama' => 'Poli '.fake()->word(),
            'aktif' => true,
        ];
    }
}
