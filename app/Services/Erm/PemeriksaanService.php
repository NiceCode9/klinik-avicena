<?php

namespace App\Services\Erm;

use App\Events\PemeriksaanDimulai;
use App\Events\PemeriksaanSelesai;
use App\Models\Erm\Diagnosis;
use App\Models\Erm\Pemeriksaan;
use App\Models\Master\Dokter;
use App\Models\Registrasi\Antrean;
use App\Models\Registrasi\Kunjungan;
use App\Models\Sys\LogAktivitas;
use App\Models\User;
use App\Services\Erm\Exceptions\DataTerkunciException;
use App\Services\Registrasi\AntreanService;
use App\Services\Registrasi\StatusKunjunganService;
use Illuminate\Support\Facades\DB;

class PemeriksaanService
{
    public function __construct(
        private StatusKunjunganService $statusKunjungan,
        private AntreanService $antrean,
    ) {}

    public function mulai(Antrean|Kunjungan $sumber, Dokter $dokter, User $oleh): Pemeriksaan
    {
        return DB::transaction(function () use ($sumber, $dokter, $oleh) {
            if ($sumber instanceof Antrean) {
                $antrean = $sumber->fresh();
                $kunjungan = $antrean->kunjungan;
            } else {
                $kunjungan = $sumber->fresh();
                $antrean = $kunjungan->antrean()
                    ->whereIn('status', ['menunggu', 'dipanggil'])
                    ->orderByDesc('id')
                    ->first();
            }

            $this->statusKunjungan->pindahkan($kunjungan, 'diperiksa', $oleh);

            if ($antrean) {
                if ($antrean->status === 'menunggu') {
                    $this->antrean->panggil($antrean);
                    $antrean->refresh();
                }
                if ($antrean->status === 'dipanggil') {
                    $this->antrean->layani($antrean);
                    $antrean->refresh();
                }
            }

            if ($kunjungan->waktu_mulai === null) {
                $kunjungan->update(['waktu_mulai' => now()]);
            }

            $pemeriksaan = Pemeriksaan::create([
                'kunjungan_id' => $kunjungan->id,
                'antrean_id' => $antrean?->id,
                'poli_id' => $dokter->poli_id,
                'dokter_id' => $dokter->id,
            ]);

            LogAktivitas::catat($oleh, 'mulai_pemeriksaan', 'erm_pemeriksaan', $pemeriksaan->id);

            PemeriksaanDimulai::dispatch($pemeriksaan);

            return $pemeriksaan->fresh();
        });
    }

    public function simpanDraf(Pemeriksaan $pemeriksaan, array $data, User $oleh): Pemeriksaan
    {
        $this->pastikanTidakTerkunci($pemeriksaan);

        return DB::transaction(function () use ($pemeriksaan, $data, $oleh) {
            $terkunci = Pemeriksaan::whereKey($pemeriksaan->id)->lockForUpdate()->firstOrFail();
            $this->pastikanTidakTerkunci($terkunci);

            $terkunci->update([
                'subjektif' => $data['subjektif'] ?? $terkunci->subjektif,
                'objektif' => $data['objektif'] ?? $terkunci->objektif,
                'asesmen' => $data['asesmen'] ?? $terkunci->asesmen,
                'rencana' => $data['rencana'] ?? $terkunci->rencana,
            ]);

            $this->simpanDiagnosis($terkunci, $data['diagnosis'] ?? []);

            LogAktivitas::catat($oleh, 'simpan_draf_pemeriksaan', 'erm_pemeriksaan', $terkunci->id);

            return $terkunci->fresh();
        });
    }

    public function simpanFinal(Pemeriksaan $pemeriksaan, array $data, User $oleh): Pemeriksaan
    {
        $this->pastikanTidakTerkunci($pemeriksaan);

        return DB::transaction(function () use ($pemeriksaan, $data, $oleh) {
            $terkunci = Pemeriksaan::whereKey($pemeriksaan->id)->lockForUpdate()->firstOrFail();
            $this->pastikanTidakTerkunci($terkunci);

            $diagnosis = $data['diagnosis'] ?? [];
            if (! $this->adaDiagnosisPrimer($diagnosis)) {
                throw new \InvalidArgumentException('Minimal satu diagnosis primer wajib diisi.');
            }

            $kodeCaraKeluar = trim((string) ($data['kode_cara_keluar'] ?? ''));
            $namaCaraKeluar = trim((string) ($data['nama_cara_keluar'] ?? ''));
            if ($kodeCaraKeluar === '' || $namaCaraKeluar === '') {
                throw new \InvalidArgumentException('Cara keluar wajib diisi.');
            }

            $terkunci->update([
                'subjektif' => $data['subjektif'] ?? $terkunci->subjektif,
                'objektif' => $data['objektif'] ?? $terkunci->objektif,
                'asesmen' => $data['asesmen'] ?? $terkunci->asesmen,
                'rencana' => $data['rencana'] ?? $terkunci->rencana,
            ]);

            $this->simpanDiagnosis($terkunci, $diagnosis);

            $kunjungan = $terkunci->kunjungan;
            $kunjungan->update([
                'kode_cara_keluar' => $kodeCaraKeluar,
                'nama_cara_keluar' => $namaCaraKeluar,
                'waktu_selesai' => now(),
            ]);

            $this->statusKunjungan->sinkron($kunjungan, $oleh);

            LogAktivitas::catat($oleh, 'final_pemeriksaan', 'erm_pemeriksaan', $terkunci->id);

            PemeriksaanSelesai::dispatch($terkunci);

            return $terkunci->fresh();
        });
    }

    private function pastikanTidakTerkunci(Pemeriksaan $pemeriksaan): void
    {
        if ($pemeriksaan->dikunci) {
            throw new DataTerkunciException;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $diagnosis
     */
    private function simpanDiagnosis(Pemeriksaan $pemeriksaan, array $diagnosis): void
    {
        // replace all diagnoses
        $pemeriksaan->diagnosis()->delete();

        foreach ($diagnosis as $row) {
            $icd10Id = $row['icd10_id'] ?? null;
            if (! $icd10Id) {
                continue;
            }
            Diagnosis::create([
                'pemeriksaan_id' => $pemeriksaan->id,
                'icd10_id' => $icd10Id,
                'jenis' => ($row['primer'] ?? false) ? 'primer' : 'sekunder',
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $diagnosis
     */
    private function adaDiagnosisPrimer(array $diagnosis): bool
    {
        foreach ($diagnosis as $row) {
            if (($row['primer'] ?? false) && ! empty($row['icd10_id'])) {
                return true;
            }
        }

        return false;
    }
}
