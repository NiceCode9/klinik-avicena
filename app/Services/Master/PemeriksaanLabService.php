<?php

namespace App\Services\Master;

use App\Models\Master\PemeriksaanLab;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PemeriksaanLabService
{
    public function buat(array $data): PemeriksaanLab
    {
        return DB::transaction(function () use ($data) {
            $pemeriksaan = PemeriksaanLab::create($this->atribut($data));
            $this->buatParameter($pemeriksaan, $data['parameter'] ?? []);

            return $pemeriksaan;
        });
    }

    public function perbarui(PemeriksaanLab $pemeriksaan, array $data): void
    {
        DB::transaction(function () use ($pemeriksaan, $data) {
            $pemeriksaan->update($this->atribut($data));
            $this->sinkronParameter($pemeriksaan, $data['parameter'] ?? []);
        });
    }

    public function hapus(PemeriksaanLab $pemeriksaan): void
    {
        $pemeriksaan->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function atribut(array $data): array
    {
        return Arr::only($data, ['kode', 'nama', 'tarif', 'aktif']);
    }

    /**
     * @return array<string, mixed>
     */
    private function atributParameter(array $baris): array
    {
        return Arr::only($baris, [
            'nama_parameter',
            'satuan',
            'nilai_rujukan_min',
            'nilai_rujukan_max',
            'keterangan_rujukan',
        ]);
    }

    /**
     * Baris tanpa nama parameter (sisa baris template) diabaikan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function barisBernama(array $baris): array
    {
        return array_values(array_filter(
            $baris,
            fn ($item) => trim((string) ($item['nama_parameter'] ?? '')) !== '',
        ));
    }

    private function buatParameter(PemeriksaanLab $pemeriksaan, array $baris): void
    {
        // Urutan mengikuti posisi baris di form.
        foreach ($this->barisBernama($baris) as $posisi => $item) {
            $pemeriksaan->parameterLab()->create(
                $this->atributParameter($item) + ['urutan' => $posisi + 1],
            );
        }
    }

    private function sinkronParameter(PemeriksaanLab $pemeriksaan, array $baris): void
    {
        $idDipakai = [];

        foreach ($this->barisBernama($baris) as $posisi => $item) {
            $id = $item['id'] ?? null;
            // Cari lewat relasi agar id milik pemeriksaan lain tidak bisa diubah.
            $parameter = $id ? $pemeriksaan->parameterLab()->find($id) : null;
            $atribut = $this->atributParameter($item) + ['urutan' => $posisi + 1];

            if ($parameter) {
                $parameter->update($atribut);
            } else {
                $parameter = $pemeriksaan->parameterLab()->create($atribut);
            }

            $idDipakai[] = $parameter->id;
        }

        // Baris yang dibuang cukup di-soft delete agar hasil lab lama tetap utuh.
        foreach ($pemeriksaan->parameterLab()->whereNotIn('id', $idDipakai)->get() as $sisa) {
            $sisa->delete();
        }
    }
}
