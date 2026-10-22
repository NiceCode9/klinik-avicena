<?php

namespace App\Services\Registrasi;

use App\Models\Master\Pasien;
use App\Models\Sys\LogAktivitas;
use App\Models\Sys\Pengaturan;
use App\Models\User;
use App\Services\NikHasher;
use App\Services\Registrasi\Exceptions\NikSudahTerdaftarException;
use App\Services\Registrasi\Exceptions\NikTidakValidException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PendaftaranService
{
    public function cariByNik(string $nik): ?Pasien
    {
        $nik = $this->nikValid($nik);

        return Pasien::where('nik_hash', NikHasher::hash($nik))->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function daftarkanPasienBaru(array $data, User $oleh): Pasien
    {
        $nik = $this->nikValid((string) ($data['nik'] ?? ''));
        $nikHash = NikHasher::hash($nik);

        return DB::transaction(function () use ($data, $nik, $nikHash, $oleh) {
            if (Pasien::withTrashed()->where('nik_hash', $nikHash)->exists()) {
                throw new NikSudahTerdaftarException('NIK sudah terdaftar.');
            }

            $penghitung = Pengaturan::where('kunci', 'no_rekam_medis_terakhir')
                ->lockForUpdate()
                ->first();

            if (! $penghitung) {
                throw new RuntimeException('Penghitung no_rekam_medis belum dikonfigurasi.');
            }

            $nomor = (int) $penghitung->nilai + 1;
            $penghitung->update(['nilai' => (string) $nomor]);

            $pasien = Pasien::create($this->atribut($data) + [
                'nik' => $nik,
                'nik_hash' => $nikHash,
                'no_rekam_medis' => 'RM-'.str_pad((string) $nomor, 6, '0', STR_PAD_LEFT),
            ]);

            LogAktivitas::catat($oleh, 'tambah_pasien', 'mtr_pasien', $pasien->id, [
                'no_rekam_medis' => $pasien->no_rekam_medis,
            ]);

            return $pasien;
        });
    }

    private function nikValid(string $nik): string
    {
        $nik = trim($nik);

        if (! preg_match('/^\d{16}$/', $nik)) {
            throw new NikTidakValidException('NIK harus 16 digit angka.');
        }

        return $nik;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function atribut(array $data): array
    {
        return Arr::only($data, [
            'nama_lengkap',
            'tempat_lahir',
            'tanggal_lahir',
            'jenis_kelamin',
            'golongan_darah',
            'alamat',
            'no_telepon',
            'nama_wali',
        ]);
    }
}
