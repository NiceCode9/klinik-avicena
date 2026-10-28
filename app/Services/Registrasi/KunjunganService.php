<?php

namespace App\Services\Registrasi;

use App\Events\KunjunganDidaftarkan;
use App\Events\TtvDisimpan;
use App\Models\Master\Pasien;
use App\Models\Master\Poli;
use App\Models\Registrasi\Kunjungan;
use App\Models\Registrasi\KunjunganRiwayatStatus;
use App\Models\Registrasi\Ttv;
use App\Models\Sys\LogAktivitas;
use App\Models\Sys\Pengaturan;
use App\Models\User;
use App\Services\Registrasi\Exceptions\StatusTidakValidException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KunjunganService
{
    public function __construct(
        private AntreanService $antrean,
        private StatusKunjunganService $status,
    ) {}

    public function buatKunjunganPoli(Pasien $pasien, User $oleh): Kunjungan
    {
        return DB::transaction(function () use ($pasien, $oleh) {
            $sekarang = now();
            $tanggal = $sekarang->format('Ymd');

            Pengaturan::insertOrIgnore([
                'kunci' => 'no_kunjungan_terakhir',
                'nilai' => '0',
                'keterangan' => 'Nomor kunjungan terakhir (otomatis)',
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ]);

            $penghitung = Pengaturan::where('kunci', 'no_kunjungan_terakhir')
                ->lockForUpdate()
                ->firstOrFail();

            [$tanggalTerakhir, $nomorTerakhir] = array_pad(
                explode(':', (string) $penghitung->nilai, 2),
                2,
                '0',
            );

            $nomor = $tanggalTerakhir === $tanggal ? ((int) $nomorTerakhir) + 1 : 1;

            $penghitung->update(['nilai' => "{$tanggal}:{$nomor}"]);

            $kunjungan = Kunjungan::create([
                'no_kunjungan' => sprintf('KJ-%s-%04d', $tanggal, $nomor),
                'pasien_id' => $pasien->id,
                'tanggal_kunjungan' => $sekarang->toDateString(),
                'jenis_kunjungan' => 'poli',
                'status' => 'terdaftar',
                'waktu_datang' => $sekarang,
                'dibuat_oleh' => $oleh->id,
            ]);

            KunjunganRiwayatStatus::create([
                'kunjungan_id' => $kunjungan->id,
                'status' => 'terdaftar',
                'waktu' => $sekarang,
                'dibuat_oleh' => $oleh->id,
            ]);

            LogAktivitas::catat($oleh, 'tambah_kunjungan', 'reg_kunjungan', $kunjungan->id, [
                'no_kunjungan' => $kunjungan->no_kunjungan,
            ]);

            KunjunganDidaftarkan::dispatch($kunjungan);

            return $kunjungan;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function simpanTtv(Kunjungan $kunjungan, Poli $poli, array $data, User $oleh): Ttv
    {
        $data = $this->validasiTtv($data);

        return DB::transaction(function () use ($kunjungan, $poli, $data, $oleh) {
            $terkunci = Kunjungan::whereKey($kunjungan->id)->lockForUpdate()->firstOrFail();

            if ($terkunci->jenis_kunjungan !== 'poli') {
                throw new StatusTidakValidException('TTV hanya dapat disimpan untuk kunjungan poli.');
            }

            if ($terkunci->status !== 'terdaftar') {
                throw new StatusTidakValidException('TTV hanya dapat disimpan saat kunjungan berstatus terdaftar.');
            }

            if ($terkunci->ttv()->exists()) {
                throw new StatusTidakValidException('TTV untuk kunjungan ini sudah pernah disimpan.');
            }

            $ttv = Ttv::create($data + [
                'kunjungan_id' => $terkunci->id,
                'perawat_id' => $oleh->id,
            ]);

            $this->status->pindahkan($terkunci, 'ttv', $oleh);
            $this->antrean->ambilNomor($poli, $terkunci);
            $this->status->pindahkan($terkunci, 'menunggu', $oleh);

            LogAktivitas::catat($oleh, 'simpan_ttv', 'reg_ttv', $ttv->id);

            TtvDisimpan::dispatch($ttv);

            return $ttv;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function buatKunjunganLabMandiri(Pasien $pasien, User $oleh): Kunjungan
    {
        return DB::transaction(function () use ($pasien, $oleh) {
            $sekarang = now();
            $tanggal = $sekarang->format('Ymd');

            Pengaturan::insertOrIgnore([
                'kunci' => 'no_kunjungan_terakhir',
                'nilai' => '0',
                'keterangan' => 'Nomor kunjungan terakhir (otomatis)',
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ]);

            $penghitung = Pengaturan::where('kunci', 'no_kunjungan_terakhir')
                ->lockForUpdate()
                ->firstOrFail();

            [$tanggalTerakhir, $nomorTerakhir] = array_pad(
                explode(':', (string) $penghitung->nilai, 2),
                2,
                '0',
            );

            $nomor = $tanggalTerakhir === $tanggal ? ((int) $nomorTerakhir) + 1 : 1;

            $penghitung->update(['nilai' => "{$tanggal}:{$nomor}"]);

            $kunjungan = Kunjungan::create([
                'no_kunjungan' => sprintf('KJ-%s-%04d', $tanggal, $nomor),
                'pasien_id' => $pasien->id,
                'tanggal_kunjungan' => $sekarang->toDateString(),
                'jenis_kunjungan' => 'lab_mandiri',
                'status' => 'terdaftar',
                'waktu_datang' => $sekarang,
                'dibuat_oleh' => $oleh->id,
            ]);

            KunjunganRiwayatStatus::create([
                'kunjungan_id' => $kunjungan->id,
                'status' => 'terdaftar',
                'waktu' => $sekarang,
                'dibuat_oleh' => $oleh->id,
            ]);

            LogAktivitas::catat($oleh, 'tambah_kunjungan', 'reg_kunjungan', $kunjungan->id, [
                'no_kunjungan' => $kunjungan->no_kunjungan,
                'jenis' => 'lab_mandiri',
            ]);

            KunjunganDidaftarkan::dispatch($kunjungan);

            return $kunjungan;
        });
    }

    private function validasiTtv(array $data): array
    {
        return Validator::make($data, [
            'tekanan_darah_sistolik' => ['required', 'integer', 'between:50,300'],
            'tekanan_darah_diastolik' => ['required', 'integer', 'between:20,200'],
            'nadi' => ['required', 'integer', 'between:20,300'],
            'suhu' => ['required', 'numeric', 'between:25,45'],
            'berat_badan' => ['required', 'numeric', 'between:0.1,500'],
            'tinggi_badan' => ['nullable', 'numeric', 'between:20,300'],
            'saturasi_oksigen' => ['nullable', 'integer', 'between:0,100'],
        ], [
            'required' => 'Kolom :attribute wajib diisi.',
            'integer' => 'Kolom :attribute harus berupa angka bulat.',
            'numeric' => 'Kolom :attribute harus berupa angka.',
            'between' => 'Kolom :attribute harus antara :min dan :max.',
        ], [
            'tekanan_darah_sistolik' => 'tekanan darah sistolik',
            'tekanan_darah_diastolik' => 'tekanan darah diastolik',
            'nadi' => 'nadi',
            'suhu' => 'suhu',
            'berat_badan' => 'berat badan',
            'tinggi_badan' => 'tinggi badan',
            'saturasi_oksigen' => 'saturasi oksigen',
        ])->validate();
    }
}
