<?php

namespace App\Services\Erm;

use App\Events\PemeriksaanDimulai;
use App\Events\PemeriksaanSelesai;
use App\Models\Erm\DetailPenyakitDalam;
use App\Models\Erm\DetailSaraf;
use App\Models\Erm\Diagnosis;
use App\Models\Erm\Odontogram;
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

    public function simpanDetailPenyakitDalam(Pemeriksaan $pemeriksaan, array $data, User $oleh): DetailPenyakitDalam
    {
        $this->pastikanTidakTerkunci($pemeriksaan);

        return DB::transaction(function () use ($pemeriksaan, $data, $oleh) {
            $terkunci = Pemeriksaan::whereKey($pemeriksaan->id)->lockForUpdate()->firstOrFail();
            $this->pastikanTidakTerkunci($terkunci);

            $rso = $this->validasiStruktur($data['review_sistem_organ'] ?? null, [
                'kardiovaskular', 'respirasi', 'gastrointestinal', 'genitourinari',
                'neurologi', 'endokrin', 'hematologi', 'muskuloskeletal',
            ]);

            $detail = DetailPenyakitDalam::updateOrCreate(
                ['pemeriksaan_id' => $terkunci->id],
                [
                    'riwayat_penyakit_kronis' => $data['riwayat_penyakit_kronis'] ?? null,
                    'status_metabolik' => $data['status_metabolik'] ?? null,
                    'review_sistem_organ' => $rso,
                ],
            );

            LogAktivitas::catat($oleh, 'simpan_detail_penyakit_dalam', 'erm_detail_penyakit_dalam', $detail->id);

            return $detail->fresh();
        });
    }

    public function simpanDetailSaraf(Pemeriksaan $pemeriksaan, array $data, User $oleh): DetailSaraf
    {
        $this->pastikanTidakTerkunci($pemeriksaan);

        return DB::transaction(function () use ($pemeriksaan, $data, $oleh) {
            $terkunci = Pemeriksaan::whereKey($pemeriksaan->id)->lockForUpdate()->firstOrFail();
            $this->pastikanTidakTerkunci($terkunci);

            $motorik = $this->validasiStruktur($data['evaluasi_motorik'] ?? null, [
                'kekuatan_otot', 'tonus', 'refleks', 'koordinasi', 'catatan',
            ]);
            $sensorik = $this->validasiStruktur($data['evaluasi_sensorik'] ?? null, [
                'nyeri', 'suhu', 'getar', 'posisi', 'catatan',
            ]);
            $kranial = $this->validasiStruktur($data['saraf_kranial'] ?? null, [
                'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII',
            ]);

            $detail = DetailSaraf::updateOrCreate(
                ['pemeriksaan_id' => $terkunci->id],
                [
                    'evaluasi_motorik' => $motorik,
                    'evaluasi_sensorik' => $sensorik,
                    'saraf_kranial' => $kranial,
                ],
            );

            LogAktivitas::catat($oleh, 'simpan_detail_saraf', 'erm_detail_saraf', $detail->id);

            return $detail->fresh();
        });
    }

    /**
     * @param  array<int, string>  $kunciDiizinkan
     * @return array<string, mixed>|null
     */
    private function validasiStruktur(mixed $data, array $kunciDiizinkan): ?array
    {
        if ($data === null) {
            return null;
        }
        if (! is_array($data)) {
            throw new \InvalidArgumentException('Data detail harus berupa objek/array.');
        }
        $hasil = [];
        foreach ($data as $kunci => $nilai) {
            if (! in_array((string) $kunci, $kunciDiizinkan, true)) {
                throw new \InvalidArgumentException("Kunci tidak diizinkan: {$kunci}.");
            }
            if ($nilai !== null && ! is_string($nilai) && ! is_numeric($nilai)) {
                throw new \InvalidArgumentException("Nilai untuk {$kunci} harus teks/angka.");
            }
            $hasil[(string) $kunci] = $nilai;
        }

        return $hasil;
    }

    public function simpanOdontogram(Pemeriksaan $pemeriksaan, array $data, User $oleh): void
    {
        $this->pastikanTidakTerkunci($pemeriksaan);

        if ($pemeriksaan->poli?->kode !== 'GIG') {
            throw new \InvalidArgumentException('Odontogram hanya untuk poli Gigi.');
        }

        DB::transaction(function () use ($pemeriksaan, $data, $oleh) {
            $terkunci = Pemeriksaan::whereKey($pemeriksaan->id)->lockForUpdate()->firstOrFail();
            $this->pastikanTidakTerkunci($terkunci);

            $terkunci->odontogram()->delete();

            $kondisiDiizinkan = ['karies', 'tambalan', 'hilang', 'mahkota', 'sehat', 'lainnya'];
            $permukaanDiizinkan = ['mesial', 'distal', 'oklusal', 'bukal', 'lingual', 'palatal', 'seluruh'];
            $gigiValid = $this->daftarGigiFdi();

            foreach (($data['items'] ?? []) as $item) {
                $nomor = (string) ($item['nomor_gigi'] ?? '');
                if (! in_array($nomor, $gigiValid, true)) {
                    throw new \InvalidArgumentException("Nomor gigi FDI tidak valid: {$nomor}.");
                }
                $kondisi = (string) ($item['kondisi'] ?? '');
                if (! in_array($kondisi, $kondisiDiizinkan, true)) {
                    throw new \InvalidArgumentException("Kondisi tidak valid: {$kondisi}.");
                }
                $permukaan = $item['permukaan'] ?? [];
                if (! is_array($permukaan)) {
                    throw new \InvalidArgumentException('Permukaan harus berupa array.');
                }
                foreach ($permukaan as $p) {
                    if (! in_array((string) $p, $permukaanDiizinkan, true)) {
                        throw new \InvalidArgumentException("Permukaan tidak valid: {$p}.");
                    }
                }

                Odontogram::create([
                    'pemeriksaan_id' => $terkunci->id,
                    'nomor_gigi' => $nomor,
                    'permukaan' => empty($permukaan) ? null : $permukaan,
                    'kondisi' => $kondisi,
                    'tindakan' => $item['tindakan'] ?? null,
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }

            LogAktivitas::catat($oleh, 'simpan_odontogram', 'erm_odontogram', $terkunci->id);
        });
    }

    /**
     * @return array<int, string>
     */
    private function daftarGigiFdi(): array
    {
        $gigi = [];
        // dewasa
        foreach ([1, 2, 3, 4] as $kuadran) {
            for ($i = 1; $i <= 8; $i++) {
                $gigi[] = $kuadran.$i;
            }
        }
        // susu
        foreach ([5, 6, 7, 8] as $kuadran) {
            for ($i = 1; $i <= 5; $i++) {
                $gigi[] = $kuadran.$i;
            }
        }

        return $gigi;
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
