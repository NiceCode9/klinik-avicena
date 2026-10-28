<?php

namespace App\Services\Lab;

use App\Events\LabDiminta;
use App\Models\Lab\Permintaan;
use App\Models\Lab\PermintaanDetail;
use App\Models\Master\Dokter;
use App\Models\Master\Pasien;
use App\Models\Master\PemeriksaanLab;
use App\Models\Master\Poli;
use App\Models\Registrasi\Kunjungan;
use App\Models\Sys\LogAktivitas;
use App\Models\Sys\Pengaturan;
use App\Models\User;
use App\Services\Registrasi\AntreanService;
use App\Services\Registrasi\KunjunganService;
use App\Services\Registrasi\PendaftaranService;
use App\Services\Registrasi\StatusKunjunganService;
use Illuminate\Support\Facades\DB;

class LabMandiriService
{
    public function __construct(
        private KunjunganService $kunjungan,
        private AntreanService $antrean,
        private StatusKunjunganService $status,
        private PendaftaranService $pendaftaran,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function daftarkan(User $oleh, array $data): Permintaan
    {
        $dokterId = (int) ($data['dokter_id'] ?? 0);
        if ($dokterId <= 0) {
            throw new \InvalidArgumentException('Dokter lab wajib dipilih.');
        }

        $labIds = $data['pemeriksaan_lab_ids'] ?? ($data['pemeriksaan_lab_id'] ?? []);
        if (! is_array($labIds)) {
            $labIds = [$labIds];
        }
        $labIds = array_values(array_filter($labIds));
        if ($labIds === []) {
            throw new \InvalidArgumentException('Minimal satu pemeriksaan lab wajib dipilih.');
        }

        Dokter::findOrFail($dokterId);

        return DB::transaction(function () use ($oleh, $data, $dokterId, $labIds) {
            $pasien = $this->resolvePasien($data, $oleh);

            $kunjungan = $this->kunjungan->buatKunjunganLabMandiri($pasien, $oleh);

            $poliLab = Poli::where('kode', 'LAB')->firstOrFail();
            $antrean = $this->antrean->ambilNomor($poliLab, $kunjungan);

            $this->status->pindahkan($kunjungan, 'lab', $oleh);

            // kunjungan tidak menyimpan antrean_id; antrean_id disimpan di permintaan.

            foreach ($labIds as $id) {
                PemeriksaanLab::findOrFail((int) $id);
            }

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
                'sumber' => 'mandiri',
                'rujukan_lab_id' => null,
                'dokter_id' => $dokterId,
                'petugas_id' => $oleh->id,
                'status' => 'menunggu',
                'dikunci' => false,
                'antrean_id' => $antrean->id,
            ]);

            foreach ($labIds as $id) {
                $master = PemeriksaanLab::findOrFail((int) $id);
                PermintaanDetail::create([
                    'permintaan_id' => $permintaan->id,
                    'pemeriksaan_lab_id' => (int) $id,
                    'tarif' => $master->tarif,
                ]);
            }

            LogAktivitas::catat($oleh, 'pendaftaran_lab_mandiri', 'lab_permintaan', $permintaan->id, [
                'no_permintaan' => $permintaan->no_permintaan,
            ]);

            LabDiminta::dispatch($permintaan);

            return $permintaan->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolvePasien(array $data, User $oleh): Pasien
    {
        if (isset($data['pasien']) && $data['pasien'] instanceof Pasien) {
            return $data['pasien'];
        }
        if (isset($data['pasien_id'])) {
            return Pasien::findOrFail((int) $data['pasien_id']);
        }

        $nik = trim((string) ($data['nik'] ?? ''));
        if ($nik !== '') {
            $found = $this->pendaftaran->cariByNik($nik);
            if ($found !== null) {
                return $found;
            }
        }

        if (isset($data['nama_lengkap'])) {
            return $this->pendaftaran->daftarkanPasienBaru($data, $oleh);
        }

        throw new \InvalidArgumentException('Data pasien tidak lengkap.');
    }
}
