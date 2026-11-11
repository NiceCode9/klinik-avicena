<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DataAwalSeeder extends Seeder
{
    public function run(): void
    {
        $waktu = ['created_at' => now(), 'updated_at' => now()];

        DB::table('sys_peran')->insert(array_map(fn ($r) => $r + $waktu, [
            ['nama' => 'admin', 'keterangan' => 'Administrator sistem'],
            ['nama' => 'resepsionis', 'keterangan' => 'Pendaftaran pasien'],
            ['nama' => 'perawat', 'keterangan' => 'Pendaftaran dan TTV'],
            ['nama' => 'dokter', 'keterangan' => 'Dokter spesialis'],
            ['nama' => 'petugas_lab', 'keterangan' => 'Petugas laboratorium'],
            ['nama' => 'apoteker', 'keterangan' => 'Apoteker / petugas farmasi'],
            ['nama' => 'kasir', 'keterangan' => 'Kasir'],
        ]));

        DB::table('mtr_poli')->insert(array_map(fn ($r) => $r + $waktu, [
            ['kode' => 'INT', 'nama' => 'Poli Penyakit Dalam'],
            ['kode' => 'GIG', 'nama' => 'Poli Gigi'],
            ['kode' => 'SRF', 'nama' => 'Poli Saraf'],
            ['kode' => 'LAB', 'nama' => 'Laboratorium'],
        ]));

        DB::table('mtr_metode_bayar')->insert(array_map(fn ($r) => $r + $waktu, [
            ['nama' => 'Tunai'],
            ['nama' => 'Transfer Bank'],
            ['nama' => 'QRIS'],
        ]));

        DB::table('sys_pengaturan')->insert(array_map(fn ($r) => $r + $waktu, [
            ['kunci' => 'biaya_jasa_racik', 'nilai' => '0', 'keterangan' => 'Biaya flat jasa racik per resep racikan (Rp)'],
            ['kunci' => 'embalase', 'nilai' => '0', 'keterangan' => 'Biaya embalase per item obat reguler (Rp)'],
            ['kunci' => 'uang_kopi', 'nilai' => '0', 'keterangan' => 'Uang kopi per transaksi (Rp)'],
            ['kunci' => 'simpanan_default', 'nilai' => '0', 'keterangan' => 'Simpanan default per transaksi (Rp)'],
            ['kunci' => 'margin_bebas', 'nilai' => '0', 'keterangan' => 'Margin harga jual golongan bebas'],
            ['kunci' => 'margin_bebas_terbatas', 'nilai' => '0', 'keterangan' => 'Margin harga jual golongan bebas terbatas'],
            ['kunci' => 'margin_keras', 'nilai' => '0', 'keterangan' => 'Margin harga jual golongan keras'],
            ['kunci' => 'margin_narkotika', 'nilai' => '0', 'keterangan' => 'Margin harga jual golongan narkotika'],
            ['kunci' => 'margin_psikotropika', 'nilai' => '0', 'keterangan' => 'Margin harga jual golongan psikotropika'],
            ['kunci' => 'retur_pasien_diizinkan', 'nilai' => '1', 'keterangan' => '1 = retur obat dari pasien diizinkan'],
            ['kunci' => 'batas_peringatan_kedaluwarsa_hari', 'nilai' => '90', 'keterangan' => 'Peringatan obat mendekati kedaluwarsa'],
        ]));

        // Akun admin awal: GANTI kata sandi setelah login pertama.
        DB::table('sys_pengguna')->insert([
            'nama' => 'Administrator',
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('ganti-kata-sandi-ini'),
            'peran_id' => DB::table('sys_peran')->where('nama', 'admin')->value('id'),
            'aktif' => true,
        ] + $waktu);

        // Menu + hak akses awal (menjalankan menu:sinkron di dalamnya)
        $this->call(HakAksesAwalSeeder::class);
    }
}
