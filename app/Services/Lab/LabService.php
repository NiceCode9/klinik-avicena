<?php

namespace App\Services\Lab;

use App\Events\LabDikunci;
use App\Models\Lab\Hasil;
use App\Models\Lab\Permintaan;
use App\Models\Lab\PermintaanDetail;
use App\Models\Master\Dokter;
use App\Models\Master\ParameterLab;
use App\Models\Registrasi\Antrean;
use App\Models\Registrasi\Kunjungan;
use App\Models\Sys\LogAktivitas;
use App\Models\User;
use App\Services\Billing\TagihanService;
use App\Services\Erm\Exceptions\DataTerkunciException;
use App\Services\Registrasi\StatusKunjunganService;
use Illuminate\Support\Facades\DB;

class LabService
{
    public function __construct(
        private StatusKunjunganService $statusKunjungan,
        private TagihanService $tagihan,
    ) {}

    public function mulaiProses(Permintaan $permintaan, ?User $oleh = null): Permintaan
    {
        return DB::transaction(function () use ($permintaan, $oleh) {
            $terkunci = Permintaan::whereKey($permintaan->id)->lockForUpdate()->firstOrFail();

            if ($terkunci->dikunci) {
                throw new DataTerkunciException;
            }

            $perubahan = ['status' => 'diproses'];
            if ($oleh !== null) {
                $perubahan['petugas_id'] = $oleh->id;
            }
            $terkunci->update($perubahan);

            if ($terkunci->antrean_id !== null) {
                $antrean = Antrean::whereKey($terkunci->antrean_id)->lockForUpdate()->first();
                if ($antrean !== null && $antrean->status === 'menunggu') {
                    $antrean->update(['status' => 'dipanggil', 'waktu_panggil' => now()]);
                    $antrean->refresh();
                }
                if ($antrean !== null && in_array($antrean->status, ['menunggu', 'dipanggil'], true)) {
                    $antrean->update(['status' => 'dilayani']);
                }
            }

            if ($oleh !== null) {
                LogAktivitas::catat($oleh, 'mulai_proses_lab', 'lab_permintaan', $terkunci->id);
            }

            return $terkunci->fresh();
        });
    }

    public function simpanHasil($sumber, $data = [], ?User $oleh = null): mixed
    {
        if ($data instanceof User) {
            $oleh = $data;
            $data = [];
        }
        if (! is_array($data)) {
            $data = [];
        }

        $detail = $this->resolveDetail($sumber, $data);

        return DB::transaction(function () use ($detail, $data, $oleh) {
            $terkunciDetail = PermintaanDetail::whereKey($detail->id)->lockForUpdate()->firstOrFail();
            $permintaan = Permintaan::whereKey($terkunciDetail->permintaan_id)->lockForUpdate()->firstOrFail();

            if ($permintaan->dikunci) {
                throw new DataTerkunciException;
            }

            // D22: hasil hanya boleh disimpan setelah spesimen diterima.
            if ($terkunciDetail->spesimen_id !== null) {
                $spesimen = $terkunciDetail->spesimen()->first();
                if ($spesimen === null || $spesimen->status !== 'diterima') {
                    throw new \InvalidArgumentException('Hasil lab hanya dapat disimpan setelah spesimen diterima.');
                }
            }
            // TODO(Q9): pemeriksaan tanpa spesimen (spesimen_id null) diizinkan sementara; pertimbangkan wajibkan spesimen (D22) setelah alur lab final.

            $baris = $this->ekstrakHasil($terkunciDetail, $data);

            $hasilTersimpan = [];
            foreach ($baris as $row) {
                $parameterLabId = (int) ($row['parameter_lab_id'] ?? 0);
                $parameter = ParameterLab::findOrFail($parameterLabId);
                if ($parameter->pemeriksaan_lab_id !== $terkunciDetail->pemeriksaan_lab_id) {
                    throw new \InvalidArgumentException('Parameter lab tidak sesuai dengan pemeriksaan.');
                }

                $nilai = (string) ($row['nilai'] ?? '');
                $satuan = array_key_exists('satuan', $row) && $row['satuan'] !== null
                    ? (string) $row['satuan']
                    : $parameter->satuan;
                $nilaiRujukan = array_key_exists('nilai_rujukan', $row) && $row['nilai_rujukan'] !== null
                    ? (string) $row['nilai_rujukan']
                    : $this->formatNilaiRujukan($parameter);
                $penanda = self::tentukanPenanda($nilai, $parameter->nilai_rujukan_min, $parameter->nilai_rujukan_max);

                $hasil = Hasil::updateOrCreate(
                    [
                        'permintaan_detail_id' => $terkunciDetail->id,
                        'parameter_lab_id' => $parameter->id,
                    ],
                    [
                        'nilai' => $nilai,
                        'satuan' => $satuan,
                        'nilai_rujukan' => $nilaiRujukan,
                        'penanda' => $penanda,
                        'diinput_oleh' => $oleh?->id,
                    ],
                );

                $hasilTersimpan[] = $hasil->fresh();
            }

            if ($oleh !== null) {
                LogAktivitas::catat($oleh, 'simpan_hasil_lab', 'lab_hasil', $hasilTersimpan[0]->id ?? null);
            }

            return count($hasilTersimpan) === 1 ? $hasilTersimpan[0] : $hasilTersimpan;
        });
    }

    public function kunci($sumber, $arg1 = null, $arg2 = null, $arg3 = null): Permintaan
    {
        $oleh = null;
        $dokter = null;
        foreach ([$arg1, $arg2, $arg3] as $arg) {
            if ($arg instanceof User) {
                $oleh = $arg;
            } elseif ($arg instanceof Dokter) {
                $dokter = $arg;
            }
        }

        $permintaan = $sumber instanceof Permintaan
            ? $sumber
            : ($sumber instanceof PermintaanDetail ? $sumber->permintaan : Permintaan::findOrFail((int) $sumber));

        return DB::transaction(function () use ($permintaan, $oleh, $dokter) {
            $terkunci = Permintaan::whereKey($permintaan->id)->lockForUpdate()->firstOrFail();

            if ($terkunci->dikunci) {
                throw new DataTerkunciException;
            }

            if ($dokter !== null) {
                $terkunci->dokter_id = $dokter->id;
            }

            if ($terkunci->dokter_id === null) {
                throw new \InvalidArgumentException('Dokter lab wajib diisi sebelum hasil dikunci.');
            }

            // semua detail wajib punya hasil
            $details = $terkunci->permintaanDetail()->with('hasil')->get();
            foreach ($details as $detail) {
                if ($detail->hasil()->count() === 0) {
                    throw new \InvalidArgumentException('Semua item pemeriksaan harus memiliki hasil sebelum dikunci.');
                }
            }

            $kunjungan = Kunjungan::whereKey($terkunci->kunjungan_id)->lockForUpdate()->first();

            $terkunci->update([
                'dokter_id' => $terkunci->dokter_id,
                'dikunci' => true,
                'dikunci_pada' => now(),
                'status' => 'selesai',
            ]);

            if ($terkunci->antrean_id !== null) {
                Antrean::whereKey($terkunci->antrean_id)->update([
                    'status' => 'selesai',
                    'waktu_selesai' => now(),
                ]);
            }

            if ($oleh !== null && $kunjungan !== null) {
                $this->tagihan->susunLab($kunjungan);
                $this->statusKunjungan->sinkron($kunjungan, $oleh);
            }

            if ($oleh !== null) {
                LogAktivitas::catat($oleh, 'kunci_hasil_lab', 'lab_permintaan', $terkunci->id);
            }

            LabDikunci::dispatch($terkunci);

            return $terkunci->fresh();
        });
    }

    public static function tentukanPenanda($nilai, $min = null, $max = null): ?string
    {
        if (! is_numeric($nilai)) {
            return null;
        }
        if ($min !== null && ! is_numeric($min)) {
            return null;
        }
        if ($max !== null && ! is_numeric($max)) {
            return null;
        }

        $v = (float) $nilai;

        if ($min !== null && $v < (float) $min) {
            return 'rendah';
        }
        if ($max !== null && $v > (float) $max) {
            return 'tinggi';
        }

        return 'normal';
    }

    private function resolveDetail($sumber, array $data): PermintaanDetail
    {
        if ($sumber instanceof PermintaanDetail) {
            return $sumber;
        }
        if ($sumber instanceof Permintaan) {
            if (isset($data['permintaan_detail_id'])) {
                return PermintaanDetail::findOrFail((int) $data['permintaan_detail_id']);
            }
            if (isset($data['detail_id'])) {
                return PermintaanDetail::findOrFail((int) $data['detail_id']);
            }
            $details = $sumber->permintaanDetail()->get();
            if ($details->count() === 1) {
                return $details->first();
            }
            throw new \InvalidArgumentException('Permintaan memiliki lebih dari satu detail; sebutkan detail_id.');
        }
        if (is_int($sumber) || (is_string($sumber) && ctype_digit($sumber))) {
            return PermintaanDetail::findOrFail((int) $sumber);
        }
        throw new \InvalidArgumentException('Sumber simpan hasil tidak valid.');
    }

    private function ekstrakHasil(PermintaanDetail $detail, array $data): array
    {
        if (isset($data['hasil']) && is_array($data['hasil'])) {
            $baris = [];
            foreach ($data['hasil'] as $item) {
                if (is_array($item)) {
                    $baris = array_merge($baris, $this->ekstrakHasil($detail, $item));
                } else {
                    $baris[] = [
                        'parameter_lab_id' => null,
                        'nilai' => $item,
                    ];
                }
            }
            // resolve parameter id jika kosong lewat detail
            foreach ($baris as $i => $row) {
                if (empty($row['parameter_lab_id'])) {
                    $params = $detail->pemeriksaanLab->parameterLab()->orderBy('urutan')->get();
                    if (isset($params[$i])) {
                        $baris[$i]['parameter_lab_id'] = $params[$i]->id;
                    }
                }
            }

            return $baris;
        }

        if (isset($data['parameter_lab_id'])) {
            return [[
                'parameter_lab_id' => $data['parameter_lab_id'],
                'nilai' => $data['nilai'] ?? null,
                'satuan' => $data['satuan'] ?? null,
                'nilai_rujukan' => $data['nilai_rujukan'] ?? null,
            ]];
        }

        // kunci numerik -> id parameter
        $numerik = [];
        foreach ($data as $k => $v) {
            if ((is_int($k) || (is_string($k) && ctype_digit($k))) && ! in_array($k, ['nilai', 'satuan'], true)) {
                $numerik[$k] = $v;
            }
        }
        if ($numerik !== [] && ! isset($data['nilai'])) {
            $baris = [];
            foreach ($numerik as $paramId => $nilai) {
                $baris[] = ['parameter_lab_id' => $paramId, 'nilai' => $nilai];
            }

            return $baris;
        }

        if (isset($data['nilai'])) {
            $params = $detail->pemeriksaanLab->parameterLab()->orderBy('urutan')->get();
            if ($params->count() === 1) {
                return [[
                    'parameter_lab_id' => $params->first()->id,
                    'nilai' => $data['nilai'],
                    'satuan' => $data['satuan'] ?? null,
                    'nilai_rujukan' => $data['nilai_rujukan'] ?? null,
                ]];
            }
        }

        throw new \InvalidArgumentException('Data hasil lab tidak valid.');
    }

    private function formatNilaiRujukan(ParameterLab $parameter): ?string
    {
        $min = $parameter->nilai_rujukan_min;
        $max = $parameter->nilai_rujukan_max;
        if ($min === null && $max === null) {
            return $parameter->keterangan_rujukan;
        }

        return trim(($min !== null ? $min : '').' - '.($max !== null ? $max : ''));
    }
}
