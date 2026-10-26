<?php

namespace Database\Seeders;

use App\Models\Master\Dokter;
use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Sys\Peran;
use App\Models\User;
use Illuminate\Database\Seeder;

class DataUjiCobaSeeder extends Seeder
{
    public function run(): void
    {
        $peranDokter = Peran::where('nama', 'dokter')->first();

        foreach (Poli::where('aktif', true)->get() as $poli) {
            $dokter = Dokter::firstOrCreate(
                ['nama' => 'Dokter '.$poli->kode],
                ['poli_id' => $poli->id, 'aktif' => true],
            );

            if ($peranDokter && ! User::where('username', 'dokter_'.strtolower($poli->kode))->exists()) {
                User::create([
                    'nama' => 'Dokter '.$poli->kode,
                    'username' => 'dokter_'.strtolower($poli->kode),
                    'email' => 'dokter_'.strtolower($poli->kode).'@klinik.test',
                    'password' => 'password',
                    'peran_id' => $peranDokter->id,
                    'dokter_id' => $dokter->id,
                    'aktif' => true,
                ]);
            }
        }

        // 8 pasien uji coba
        for ($i = 1; $i <= 8; $i++) {
            Pasien::factory()->create();
        }
    }
}
