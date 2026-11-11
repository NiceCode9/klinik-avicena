<?php

namespace App\Services\Registrasi;

use App\Models\Billing\Tagihan;
use App\Models\Lab\Permintaan;
use App\Models\Registrasi\Kunjungan;
use App\Models\Registrasi\KunjunganRiwayatStatus;
use App\Models\Sys\LogAktivitas;
use App\Models\User;
use App\Services\Billing\TagihanService;
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
            'diperiksa' => ['lab', 'farmasi', 'batal'],
            'lab' => ['farmasi', 'selesai', 'batal'],
            'farmasi' => ['lab', 'selesai', 'batal'],
            'selesai' => [],
            'batal' => [],
        ],
        'lab_mandiri' => [
            'terdaftar' => ['lab', 'batal'],
            'ttv' => [],
            'menunggu' => [],
            'diperiksa' => [],
            'lab' => ['selesai', 'batal'],
            'farmasi' => [],
            'selesai' => [],
            'batal' => [],
        ],
        'apotek_umum' => [
            'terdaftar' => ['farmasi', 'batal'],
            'ttv' => [],
            'menunggu' => [],
            'diperiksa' => [],
            'lab' => [],
            'farmasi' => ['selesai', 'batal'],
            'selesai' => [],
            'batal' => [],
        ],
    ];

    public function __construct(
        private TagihanService $tagihan,
    ) {}

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

            if ($this->adaPermintaanBelumTerkunci($kunjungan)) {
                return 'lab';
            }

            $lab = $this->tagihan($kunjungan, 'lab');
            if ($lab !== null && ! $this->tagihan->lunas($lab)) {
                return 'lab';
            }

            $farmasi = $this->tagihan($kunjungan, 'farmasi');
            if ($farmasi === null) {
                return null;
            }

            return $this->tagihan->lunas($farmasi) ? 'selesai' : 'farmasi';
        }

        if ($kunjungan->jenis_kunjungan === 'lab_mandiri' && $kunjungan->status === 'lab') {
            if ($this->adaPermintaanBelumTerkunci($kunjungan)) {
                return 'lab';
            }

            $lab = $this->tagihan($kunjungan, 'lab');
            if ($lab !== null && ! $this->tagihan->lunas($lab)) {
                return 'lab';
            }

            return 'selesai';
        }

        if ($kunjungan->jenis_kunjungan === 'apotek_umum') {
            $farmasi = $this->tagihan($kunjungan, 'farmasi');

            if ($farmasi === null) {
                return null;
            }

            return $this->tagihan->lunas($farmasi) ? 'selesai' : 'farmasi';
        }

        return null;
    }

    private function tagihan(Kunjungan $kunjungan, string $jenis): ?Tagihan
    {
        return Tagihan::where('kunjungan_id', $kunjungan->id)
            ->where('jenis', $jenis)
            ->lockForUpdate()
            ->first();
    }

    private function adaPermintaanBelumTerkunci(Kunjungan $kunjungan): bool
    {
        return $kunjungan->permintaan()
            ->where('status', '!=', 'batal')
            ->lockForUpdate()
            ->get(['id', 'dikunci'])
            ->contains(fn (Permintaan $permintaan) => ! $permintaan->dikunci);
    }
}
