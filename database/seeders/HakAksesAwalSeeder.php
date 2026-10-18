<?php

namespace Database\Seeders;

use App\Models\Sys\HakAkses;
use App\Models\Sys\Menu;
use App\Models\Sys\Peran;
use App\Services\IzinService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

/**
 * Hak akses awal per peran. Huruf: L = lihat, T = tambah, U = ubah, H = hapus.
 * Peran admin diurus oleh `menu:sinkron` (akses penuh). Silakan sesuaikan lewat UI Hak Akses.
 */
class HakAksesAwalSeeder extends Seeder
{
    public function run(): void
    {
        Artisan::call('menu:sinkron');

        $matriks = [
            'resepsionis' => [
                'dasbor' => 'L',
                'pendaftaran.pasien' => 'LTU',
                'pendaftaran.kunjungan' => 'LTU',
            ],
            'perawat' => [
                'dasbor' => 'L',
                'pendaftaran.pasien' => 'LTU',
                'pendaftaran.kunjungan' => 'LTU',
                'pendaftaran.ttv' => 'LTU',
            ],
            'dokter' => [
                'dasbor' => 'L',
                'pendaftaran.pasien' => 'L',
                'erm.pemeriksaan' => 'LTU',
                'erm.resep' => 'LTU',
                'erm.rujukan_lab' => 'LT',
                'lab.hasil' => 'L',
                'master.icd10' => 'L',
            ],
            'petugas_lab' => [
                'dasbor' => 'L',
                'pendaftaran.pasien' => 'LT',          // boleh mendaftarkan pasien baru, tidak boleh ubah identitas
                'pendaftaran.lab_mandiri' => 'LT',
                'lab.antrean' => 'LU',
                'lab.hasil' => 'LTU',
                'master.pemeriksaan_lab' => 'L',
            ],
            'apoteker' => [
                'dasbor' => 'L',
                'apotek.resep' => 'LU',
                'apotek.penjualan' => 'LTU',
                'apotek.stok' => 'L',
                'apotek.penerimaan' => 'LTU',
                'apotek.retur_supplier' => 'LTU',
                'apotek.retur_pasien' => 'LTU',
                'apotek.opname' => 'LTU',
                'master.obat' => 'LTU',
                'master.racikan' => 'LTU',
                'master.supplier' => 'LTU',
            ],
            'kasir' => [
                'dasbor' => 'L',
                'kasir.tagihan' => 'L',
                'kasir.pembayaran' => 'LT',
                'kasir.laporan' => 'L',
            ],
        ];

        foreach ($matriks as $namaPeran => $izin) {
            $peran = Peran::where('nama', $namaPeran)->firstOrFail();

            foreach ($izin as $kode => $huruf) {
                $menu = Menu::where('kode', $kode)->first();
                if (! $menu) {
                    $this->command?->warn("Menu {$kode} tidak ada di config/menu.php, dilewati.");

                    continue;
                }

                HakAkses::updateOrCreate(
                    ['peran_id' => $peran->id, 'menu_id' => $menu->id],
                    [
                        'bisa_lihat' => str_contains($huruf, 'L'),
                        'bisa_tambah' => str_contains($huruf, 'T'),
                        'bisa_ubah' => str_contains($huruf, 'U'),
                        'bisa_hapus' => str_contains($huruf, 'H'),
                    ],
                );
            }

            IzinService::lupakan($peran->id);
        }
    }
}
