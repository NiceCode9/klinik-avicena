<?php

namespace App\Services\Registrasi;

use App\Models\Master\Poli;
use App\Models\Registrasi\Antrean;
use App\Models\Registrasi\Kunjungan;
use App\Models\Registrasi\PenghitungAntrean;
use App\Services\Registrasi\Exceptions\StatusAntreanTidakValidException;
use Illuminate\Support\Facades\DB;

class AntreanService
{
    public function ambilNomor(Poli $poli, Kunjungan $kunjungan): Antrean
    {
        return DB::transaction(function () use ($poli, $kunjungan) {
            $tanggal = today()->toDateString();

            PenghitungAntrean::insertOrIgnore([
                'poli_id' => $poli->id,
                'tanggal' => $tanggal,
                'nomor_terakhir' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $penghitung = PenghitungAntrean::where('poli_id', $poli->id)
                ->where('tanggal', $tanggal)
                ->lockForUpdate()
                ->firstOrFail();

            $nomor = (int) $penghitung->nomor_terakhir + 1;
            $penghitung->update(['nomor_terakhir' => $nomor]);

            return Antrean::create([
                'kunjungan_id' => $kunjungan->id,
                'poli_id' => $poli->id,
                'tanggal' => $tanggal,
                'nomor_urut' => $nomor,
                'nomor_antrean' => sprintf('%s-%03d', $poli->kode, $nomor),
                'status' => 'menunggu',
            ]);
        });
    }

    public function panggil(Antrean $antrean): Antrean
    {
        return $this->ubahStatus($antrean, ['menunggu'], [
            'status' => 'dipanggil',
            'waktu_panggil' => now(),
        ]);
    }

    public function layani(Antrean $antrean): Antrean
    {
        return $this->ubahStatus($antrean, ['dipanggil'], ['status' => 'dilayani']);
    }

    public function selesai(Antrean $antrean): Antrean
    {
        return $this->ubahStatus($antrean, ['dilayani'], [
            'status' => 'selesai',
            'waktu_selesai' => now(),
        ]);
    }

    public function batal(Antrean $antrean): Antrean
    {
        return $this->ubahStatus($antrean, ['menunggu', 'dipanggil', 'dilayani'], [
            'status' => 'batal',
            'waktu_selesai' => now(),
        ]);
    }

    /**
     * @param  array<int, string>  $statusDiizinkan
     * @param  array<string, mixed>  $perubahan
     */
    private function ubahStatus(Antrean $antrean, array $statusDiizinkan, array $perubahan): Antrean
    {
        return DB::transaction(function () use ($antrean, $statusDiizinkan, $perubahan) {
            $terkunci = Antrean::whereKey($antrean->id)->lockForUpdate()->firstOrFail();

            if (! in_array($terkunci->status, $statusDiizinkan, true)) {
                throw new StatusAntreanTidakValidException(
                    "Status antrean {$terkunci->status} tidak dapat diubah menjadi {$perubahan['status']}."
                );
            }

            $terkunci->update($perubahan);

            return $terkunci->refresh();
        });
    }
}
