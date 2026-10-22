<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $waktu = ['created_at' => now(), 'updated_at' => now()];

        DB::table('sys_pengaturan')->insertOrIgnore([
            array_merge([
                'kunci' => 'no_rekam_medis_terakhir',
                'nilai' => '0',
                'keterangan' => 'Nomor rekam medis terakhir (otomatis)',
            ], $waktu),
        ]);
    }

    public function down(): void
    {
        // Baris penghitung dipertahankan agar nomor RM tidak berulang setelah rollback.
    }
};
