<?php

namespace App\Services\Lab;

use App\Events\SpesimenDiambil;
use App\Models\Lab\Permintaan;
use App\Models\Lab\PermintaanDetail;
use App\Models\Lab\Spesimen;
use App\Models\Sys\LogAktivitas;
use App\Models\User;
use App\Services\Erm\Exceptions\DataTerkunciException;
use Illuminate\Support\Facades\DB;

class SpesimenService
{
    public function catatPengambilan($sumber, $data = [], ?User $oleh = null): Spesimen
    {
        return $this->catatSpesimen($sumber, $data, $oleh);
    }

    public function catat($sumber, $data = [], ?User $oleh = null): Spesimen
    {
        return $this->catatSpesimen($sumber, $data, $oleh);
    }

    public function catatSpesimen($sumber, $data = [], ?User $oleh = null): Spesimen
    {
        if ($data instanceof User) {
            $oleh = $data;
            $data = [];
        }
        if (is_string($data)) {
            $data = ['kode_spesimen' => $data];
        }
        if (! is_array($data)) {
            $data = [];
        }

        $permintaan = $sumber instanceof Permintaan
            ? $sumber
            : Permintaan::findOrFail((int) $sumber);

        return DB::transaction(function () use ($permintaan, $data, $oleh) {
            $terkunci = Permintaan::whereKey($permintaan->id)->lockForUpdate()->firstOrFail();

            if ($terkunci->dikunci) {
                throw new DataTerkunciException;
            }

            $kode = isset($data['kode_spesimen']) && $data['kode_spesimen'] !== ''
                ? (string) $data['kode_spesimen']
                : null;
            $nama = isset($data['nama_spesimen']) && $data['nama_spesimen'] !== ''
                ? (string) $data['nama_spesimen']
                : null;

            if ($kode === null || $nama === null) {
                $master = $terkunci->permintaanDetail()
                    ->with('pemeriksaanLab')
                    ->first()?->pemeriksaanLab;
                $kode = $kode ?? ($master?->kode_spesimen ?: 'UNSPECIFIED');
                $nama = $nama ?? ($master?->nama ?: 'Spesimen');
            }

            $status = (string) ($data['status'] ?? 'menunggu_diambil');

            $spesimen = Spesimen::create([
                'permintaan_id' => $terkunci->id,
                'kode_spesimen' => $kode,
                'nama_spesimen' => $nama,
                'status' => $status,
                'waktu_pengambilan' => $data['waktu_pengambilan'] ?? ($status !== 'menunggu_diambil' ? now() : null),
                'diambil_oleh' => isset($data['diambil_oleh']) ? $data['diambil_oleh'] : ($oleh?->id),
                'catatan' => $data['catatan'] ?? null,
            ]);

            if ($oleh !== null) {
                LogAktivitas::catat($oleh, 'catat_spesimen', 'lab_spesimen', $spesimen->id);
            }

            SpesimenDiambil::dispatch($spesimen);

            return $spesimen->fresh();
        });
    }

    public function ubahStatus($spesimen, $statusOrCatatan = 'diterima', $catatanOrOleh = null, $oleh = null): Spesimen
    {
        // dukungan argumen fleksibel
        if ($statusOrCatatan instanceof User) {
            $status = 'diterima';
            $oleh = $statusOrCatatan;
            $catatan = null;
        } elseif (is_string($statusOrCatatan) && in_array($statusOrCatatan, ['menunggu_diambil', 'diambil', 'diterima', 'ditolak'], true)) {
            $status = $statusOrCatatan;
            if ($catatanOrOleh instanceof User) {
                $oleh = $catatanOrOleh;
                $catatan = null;
            } else {
                $catatan = $catatanOrOleh;
                // $oleh tetap
                if ($oleh === null && $catatanOrOleh instanceof User) {
                    $oleh = $catatanOrOleh;
                }
            }
        } else {
            $status = 'diterima';
            $catatan = $statusOrCatatan;
            if ($catatanOrOleh instanceof User) {
                $oleh = $catatanOrOleh;
            }
        }

        if ($oleh === null && $catatanOrOleh instanceof User) {
            $oleh = $catatanOrOleh;
        }

        $spesimenModel = $spesimen instanceof Spesimen
            ? $spesimen
            : Spesimen::findOrFail((int) $spesimen);

        $catatan = (isset($catatan) && $catatan !== null) ? (string) $catatan : null;

        return DB::transaction(function () use ($spesimenModel, $status, $catatan, $oleh) {
            $terkunci = Spesimen::whereKey($spesimenModel->id)->lockForUpdate()->firstOrFail();

            if ($status === 'ditolak' && trim((string) $catatan) === '') {
                throw new \InvalidArgumentException('Catatan wajib diisi jika spesimen ditolak.');
            }

            $izinkan = [
                'menunggu_diambil' => ['diambil', 'ditolak'],
                'diambil' => ['diterima', 'ditolak'],
                'diterima' => [],
                'ditolak' => [],
            ];

            if ($terkunci->status !== $status && ! in_array($status, $izinkan[$terkunci->status] ?? [], true)) {
                throw new \InvalidArgumentException("Status spesimen {$terkunci->status} tidak dapat diubah menjadi {$status}.");
            }

            $perubahan = ['status' => $status];
            if ($catatan !== null) {
                $perubahan['catatan'] = $catatan;
            }
            if ($terkunci->waktu_pengambilan === null && $status !== 'menunggu_diambil') {
                $perubahan['waktu_pengambilan'] = now();
            }

            $terkunci->update($perubahan);

            if ($oleh !== null) {
                LogAktivitas::catat($oleh, 'ubah_status_spesimen', 'lab_spesimen', $terkunci->id);
            }

            return $terkunci->fresh();
        });
    }

    public function ubahStatusSpesimen($spesimen, $statusOrCatatan = 'diterima', $catatanOrOleh = null, $oleh = null): Spesimen
    {
        return $this->ubahStatus($spesimen, $statusOrCatatan, $catatanOrOleh, $oleh);
    }

    public function tautkan($detail, $spesimen, ?User $oleh = null): PermintaanDetail
    {
        if ($detail instanceof Spesimen && $spesimen instanceof PermintaanDetail) {
            [$spesimen, $detail] = [$detail, $spesimen];
        }

        $detailModel = $detail instanceof PermintaanDetail
            ? $detail
            : PermintaanDetail::findOrFail((int) $detail);
        $spesimenModel = $spesimen instanceof Spesimen
            ? $spesimen
            : Spesimen::findOrFail((int) $spesimen);

        return DB::transaction(function () use ($detailModel, $spesimenModel, $oleh) {
            $terkunci = PermintaanDetail::whereKey($detailModel->id)->lockForUpdate()->firstOrFail();
            $terkunci->update(['spesimen_id' => $spesimenModel->id]);

            if ($oleh !== null) {
                LogAktivitas::catat($oleh, 'tautan_spesimen', 'lab_permintaan_detail', $terkunci->id);
            }

            return $terkunci->fresh();
        });
    }

    public function tautkanSpesimen($detail, $spesimen, ?User $oleh = null): PermintaanDetail
    {
        return $this->tautkan($detail, $spesimen, $oleh);
    }

    public function tautkanKeDetail($detail, $spesimen, ?User $oleh = null): PermintaanDetail
    {
        return $this->tautkan($detail, $spesimen, $oleh);
    }

    public function hubungkan($detail, $spesimen, ?User $oleh = null): PermintaanDetail
    {
        return $this->tautkan($detail, $spesimen, $oleh);
    }
}
