<?php

namespace App\Services\SatuSehat;

use App\Models\Master\Dokter;
use App\Models\Master\Lokasi;
use App\Models\Master\Nakes;
use App\Models\Master\Obat;
use App\Models\Master\ParameterLab;
use App\Models\Master\PemeriksaanLab;
use App\Models\Master\Poli;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PemetaanService
{
    /**
     * Definisi master yang wajib lengkap untuk SATUSEHAT.
     *
     * @return array<string, array<string, mixed>>
     */
    private function kategori(): array
    {
        return [
            'obat' => [
                'label' => 'Obat',
                'model' => Obat::class,
                'wajib' => ['kode_kfa' => 'Kode KFA aktual'],
                'rute_edit' => 'obat.edit',
                'keterangan' => 'Kode produk KFA untuk MedicationDispense.',
                'judul' => fn (Obat $obat) => $obat->nama,
                'kode' => fn (Obat $obat) => $obat->kode,
            ],
            'pemeriksaan_lab' => [
                'label' => 'Pemeriksaan Lab',
                'model' => PemeriksaanLab::class,
                'wajib' => [
                    'kode_loinc' => 'Kode LOINC',
                    'kode_spesimen' => 'Kode Spesimen',
                ],
                'rute_edit' => 'pemeriksaan-lab.edit',
                'keterangan' => 'LOINC pemeriksaan dan SNOMED spesimen.',
                'judul' => fn (PemeriksaanLab $lab) => $lab->nama,
                'kode' => fn (PemeriksaanLab $lab) => $lab->kode,
            ],
            'parameter_lab' => [
                'label' => 'Parameter Lab',
                'model' => ParameterLab::class,
                'wajib' => [
                    'kode_loinc' => 'Kode LOINC',
                    'satuan_ucum' => 'Satuan UCUM',
                ],
                'rute_edit' => 'pemeriksaan-lab.edit',
                'keterangan' => 'LOINC parameter dan UCUM satuan hasil.',
                'judul' => fn (ParameterLab $parameter) => $parameter->nama_parameter,
                'kode' => fn (ParameterLab $parameter) => $parameter->pemeriksaanLab?->kode,
                'catatan' => fn (ParameterLab $parameter) => $parameter->pemeriksaanLab?->nama,
                'rute_arg' => fn (ParameterLab $parameter) => $parameter->pemeriksaan_lab_id,
                'dengan' => ['pemeriksaanLab'],
                'saringan' => fn (Builder $query) => $query->whereHas('pemeriksaanLab'),
            ],
            'poli' => [
                'label' => 'Poli',
                'model' => Poli::class,
                'wajib' => [
                    'kode_tipe_pelayanan' => 'Kode Tipe Pelayanan',
                    'ihs_location_id' => 'IHS Location',
                ],
                'rute_edit' => 'poli.edit',
                'keterangan' => 'ServiceType Encounter dan Location IHS.',
                'judul' => fn (Poli $poli) => $poli->nama,
                'kode' => fn (Poli $poli) => $poli->kode,
            ],
            'dokter' => [
                'label' => 'Dokter',
                'model' => Dokter::class,
                'wajib' => ['ihs_practitioner_id' => 'IHS Practitioner'],
                'rute_edit' => 'dokter.edit',
                'keterangan' => 'ID Practitioner IHS.',
                'judul' => fn (Dokter $dokter) => $dokter->nama,
                'kode' => fn (Dokter $dokter) => $dokter->no_sip,
            ],
            'nakes' => [
                'label' => 'Tenaga Kesehatan',
                'model' => Nakes::class,
                'wajib' => ['ihs_practitioner_id' => 'IHS Practitioner'],
                'rute_edit' => 'nakes.edit',
                'keterangan' => 'ID Practitioner IHS.',
                'judul' => fn (Nakes $nakes) => $nakes->nama,
                'kode' => fn (Nakes $nakes) => $nakes->no_str,
            ],
            'lokasi' => [
                'label' => 'Lokasi',
                'model' => Lokasi::class,
                'wajib' => ['ihs_location_id' => 'IHS Location'],
                'rute_edit' => 'lokasi.edit',
                'keterangan' => 'ID Location IHS.',
                'judul' => fn (Lokasi $lokasi) => $lokasi->nama,
                'kode' => fn (Lokasi $lokasi) => $lokasi->kode,
            ],
        ];
    }

    /**
     * Ringkasan jumlah per kategori + daftar satu kategori yang dipilih.
     *
     * @return array{kategori: array<string, array{label: string, jumlah: int, daftar: mixed, keterangan: string}>, terpilih: string}
     */
    public function belumTerpetakan(string $kategori = 'obat', int $perHalaman = 10): array
    {
        $definisi = $this->kategori();
        $terpilih = array_key_exists($kategori, $definisi) ? $kategori : 'obat';
        $hasil = [];

        foreach ($definisi as $kunci => $item) {
            $query = $this->query($item);
            $jumlah = (clone $query)->count();
            $daftar = null;

            if ($kunci === $terpilih) {
                $daftar = $query->paginate($perHalaman)
                    ->through(fn (Model $model) => $this->baris($kunci, $item, $model));
            }

            $hasil[$kunci] = [
                'label' => $item['label'],
                'jumlah' => $jumlah,
                'daftar' => $daftar,
                'keterangan' => $item['keterangan'],
            ];
        }

        return ['kategori' => $hasil, 'terpilih' => $terpilih];
    }

    /**
     * Kolom wajib yang masih kosong pada satu item. Dipakai juga oleh job 7A.
     *
     * @return array<int, string>
     */
    public function kekurangan(string $kategori, Model $model): array
    {
        $definisi = $this->kategori()[$kategori] ?? null;

        if (! $definisi) {
            return [];
        }

        $kurang = [];

        foreach ($definisi['wajib'] as $kolom => $label) {
            if (blank($model->{$kolom})) {
                $kurang[] = $label;
            }
        }

        return $kurang;
    }

    /**
     * @param  array<string, mixed>  $definisi
     */
    private function query(array $definisi): Builder
    {
        $model = $definisi['model'];
        $query = $model::query();

        $query->where(function (Builder $q) use ($definisi) {
            foreach (array_keys($definisi['wajib']) as $kolom) {
                $q->orWhere(function (Builder $sub) use ($kolom) {
                    $sub->whereNull($kolom)->orWhere($kolom, '');
                });
            }
        });

        if (isset($definisi['dengan'])) {
            $query->with($definisi['dengan']);
        }

        if (isset($definisi['saringan'])) {
            ($definisi['saringan'])($query);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $definisi
     * @return array{id: int, judul: string, kode: ?string, catatan: ?string, kurang: array<int, string>, rute_edit: string}
     */
    private function baris(string $kategori, array $definisi, Model $model): array
    {
        $arg = isset($definisi['rute_arg']) ? ($definisi['rute_arg'])($model) : $model;

        return [
            'id' => $model->getKey(),
            'judul' => ($definisi['judul'])($model),
            'kode' => ($definisi['kode'])($model),
            'catatan' => isset($definisi['catatan']) ? ($definisi['catatan'])($model) : null,
            'kurang' => $this->kekurangan($kategori, $model),
            'rute_edit' => route($definisi['rute_edit'], $arg),
        ];
    }
}
