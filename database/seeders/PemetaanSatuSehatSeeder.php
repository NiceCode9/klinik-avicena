<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Data awal pendukung SATUSEHAT: lokasi Apotek (untuk MedicationDispense). */
class PemetaanSatuSehatSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('mtr_lokasi')->updateOrInsert(
            ['kode' => 'APT'],
            ['nama' => 'Apotek', 'aktif' => true, 'created_at' => now(), 'updated_at' => now()],
        );
    }
}
