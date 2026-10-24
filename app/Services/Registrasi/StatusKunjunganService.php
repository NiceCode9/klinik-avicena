<?php

namespace App\Services\Registrasi;

use App\Models\Erm\Resep;
use App\Models\Lab\Permintaan;
use App\Models\Registrasi\Kunjungan;
use App\Models\Registrasi\KunjunganRiwayatStatus;
use App\Models\Sys\LogAktivitas;
use App\Models\User;
use App\Services\Registrasi\Exceptions\StatusTidakValidException;
use Illuminate\Support\Facades\DB;

class StatusKunjunganService
{
    /** @var array<string, array<string, array<int, string>>> */
    private const TRANSISI = [
        'poli' => [
            'terdaftar' => ['ttv', 'batal'],
            'ttv' => ['menunggu', 'batal'],
            'menunggu' => ['diperiksa', 'batal'],
            'diperiksa' => ['lab', 'farmasi', 'kasir', 'batal'],
            'lab' => ['farmasi', 'kasir', 'batal'],
            'farmasi' => ['kasir', 'batal'],
            'kasir' => ['selesai'],
            'selesai' => [],
            'batal' => [],
        ],
        'lab_mandiri' => [
            'terdaftar' => ['lab', 'batal'],
            'ttv' => [],
            'menunggu' => [],
            'diperiksa' => [],
            'lab' => ['kasir', 'batal'],
            'farmasi' => [],
            'kasir' => ['selesai'],
            'selesai' => [],
            'batal' => [],
        ],
        'apotek_umum' => [
            'terdaftar' => ['kasir', 'batal'],
            'ttv' => [],
            'menunggu' => [],
            'diperiksa' => [],
            'lab' => [],
            'farmasi' => [],
            'kasir' => ['selesai'],
            'selesai' => [],
            'batal' => [],
        ],
    ];

    public function pindahkan(
        Kunjungan $kunjungan,
        string $status,
        User $oleh,
        ?string $alasan = null,
    ): Kunjungan {
        return DB::transaction(function () use ($kunjungan, $status, $oleh, $alasan) {
            $terkunci = Kunjungan::whereKey($kunjungan->id)->lockForUpdate()->firstOrFail();
            $tujuanSah = self::TRANSISI[$terkunci->jenis_kunjungan][$terkunci->status] ?? [];

            if (! in_array($status, $tujuanSah, true)) {
                throw new StatusTidakValidException(
                    "Status kunjungan {$terkunci->status} tidak dapat diubah menjadi {$status}."
                );
            }

            if ($status === 'batal' && trim((string) $alasan) === '') {
                throw new StatusTidakValidException('Alasan pembatalan kunjungan wajib diisi.');
            }

            $terkunci->update(['status' => $status]);

            KunjunganRiwayatStatus::create([
                'kunjungan_id' => $terkunci->id,
                'status' => $status,
                'waktu' => now(),
                'dibuat_oleh' => $oleh->id,
                'alasan' => $status === 'batal' ? trim((string) $alasan) : null,
            ]);

            LogAktivitas::catat($oleh, 'ubah_status_kunjungan', 'reg_kunjungan', $terkunci->id, [
                'status' => $status,
            ]);

            return $terkunci->refresh();
        });
    }

    public function sinkron(Kunjungan $kunjungan, User $oleh): Kunjungan
    {
        return DB::transaction(function () use ($kunjungan, $oleh) {
            $terkunci = Kunjungan::whereKey($kunjungan->id)->lockForUpdate()->firstOrFail();
            $target = $this->targetSinkron($terkunci);

            if ($target === null || $target === $terkunci->status) {
                return $terkunci;
            }

            $tujuanSah = self::TRANSISI[$terkunci->jenis_kunjungan][$terkunci->status] ?? [];

            if (! in_array($target, $tujuanSah, true)) {
                return $terkunci;
            }

            return $this->pindahkan($terkunci, $target, $oleh);
        });
    }

    private function targetSinkron(Kunjungan $kunjungan): ?string
    {
        if ($kunjungan->jenis_kunjungan === 'poli') {
            if (! in_array($kunjungan->status, ['diperiksa', 'lab', 'farmasi'], true)) {
                return null;
            }

            // Baca dengan lock agar tidak memakai snapshot lama transaksi pemanggil.
            if ($this->adaPermintaanBelumTerkunci($kunjungan)) {
                return 'lab';
            }

            $resepBelumSelesai = Resep::whereHas('pemeriksaan', function ($query) use ($kunjungan) {
                $query->where('kunjungan_id', $kunjungan->id);
            })->lockForUpdate()
                ->get(['id', 'status'])
                ->contains(fn (Resep $resep) => ! in_array($resep->status, ['diserahkan', 'batal'], true));

            return $resepBelumSelesai ? 'farmasi' : 'kasir';
        }

        if ($kunjungan->jenis_kunjungan === 'lab_mandiri' && $kunjungan->status === 'lab') {
            return $this->adaPermintaanBelumTerkunci($kunjungan) ? 'lab' : 'kasir';
        }

        return null;
    }

    private function adaPermintaanBelumTerkunci(Kunjungan $kunjungan): bool
    {
        return $kunjungan->permintaan()
            ->lockForUpdate()
            ->get(['id', 'dikunci'])
            ->contains(fn (Permintaan $permintaan) => ! $permintaan->dikunci);
    }
}
