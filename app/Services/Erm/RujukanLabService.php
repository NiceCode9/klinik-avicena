<?php

namespace App\Services\Erm;

use App\Events\LabDiminta;
use App\Models\Erm\Pemeriksaan;
use App\Models\Erm\RujukanLab;
use App\Models\Erm\RujukanLabDetail;
use App\Models\Lab\Permintaan;
use App\Models\Lab\PermintaanDetail;
use App\Models\Master\PemeriksaanLab;
use App\Models\Master\Poli;
use App\Models\Registrasi\Antrean;
use App\Models\Sys\LogAktivitas;
use App\Models\Sys\Pengaturan;
use App\Models\User;
use App\Services\Registrasi\AntreanService;
use App\Services\Registrasi\StatusKunjunganService;
use Illuminate\Support\Facades\DB;

class RujukanLabService
{
    public function __construct(
        private StatusKunjunganService $statusKunjungan,
        private AntreanService $antrean,
    ) {}

    public function buat(Pemeriksaan $pemeriksaan, array $pemeriksaanLabIds, ?string $catatan, User $oleh): RujukanLab
    {
        return DB::transaction(function () use ($pemeriksaan, $pemeriksaanLabIds, $catatan, $oleh) {
            $kunjungan = $pemeriksaan->kunjungan;

            $rujukan = RujukanLab::create([
                'pemeriksaan_id' => $pemeriksaan->id,
                'dokter_id' => $pemeriksaan->dokter_id,
                'catatan' => $catatan,
            ]);

            foreach ($pemeriksaanLabIds as $id) {
                RujukanLabDetail::create([
                    'rujukan_lab_id' => $rujukan->id,
                    'pemeriksaan_lab_id' => $id,
                ]);
            }

            // no_permintaan
            $tanggal = now()->format('Ymd');
            $penghitung = Pengaturan::firstOrCreate(
                ['kunci' => 'no_permintaan_lab_terakhir'],
                ['nilai' => '0', 'keterangan' => 'Nomor permintaan lab terakhir', 'created_at' => now(), 'updated_at' => now()],
            );
            $penghitung = Pengaturan::where('kunci', 'no_permintaan_lab_terakhir')->lockForUpdate()->first();
            [$tanggalTerakhir, $nomorTerakhir] = array_pad(explode(':', (string) $penghitung->nilai, 2), 2, '0');
            $nomor = $tanggalTerakhir === $tanggal ? ((int) $nomorTerakhir) + 1 : 1;
            $penghitung->update(['nilai' => "{$tanggal}:{$nomor}"]);
            $noPermintaan = sprintf('LP-%s-%04d', $tanggal, $nomor);

            $permintaan = Permintaan::create([
                'no_permintaan' => $noPermintaan,
                'kunjungan_id' => $kunjungan->id,
                'sumber' => 'rujukan',
                'rujukan_lab_id' => $rujukan->id,
                'dokter_id' => null,
                'petugas_id' => null,
                'status' => 'menunggu',
                'dikunci' => false,
            ]);

            foreach ($pemeriksaanLabIds as $id) {
                $master = PemeriksaanLab::findOrFail($id);
                PermintaanDetail::create([
                    'permintaan_id' => $permintaan->id,
                    'pemeriksaan_lab_id' => $id,
                    'tarif' => $master->tarif,
                ]);
            }

            // antrean LAB
            $poliLab = Poli::where('kode', 'LAB')->firstOrFail();
            $antrean = $this->antrean->ambilNomor($poliLab, $kunjungan);
            $permintaan->update(['antrean_id' => $antrean->id]);

            // status kunjungan diperiksa -> lab
            $this->statusKunjungan->pindahkan($kunjungan, 'lab', $oleh);

            LogAktivitas::catat($oleh, 'rujukan_lab', 'erm_rujukan_lab', $rujukan->id);

            LabDiminta::dispatch($rujukan);

            return $rujukan->fresh();
        });
    }
}
