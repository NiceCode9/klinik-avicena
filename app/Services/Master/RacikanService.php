<?php

namespace App\Services\Master;

use App\Models\Master\Racikan;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class RacikanService
{
    public function buat(array $data): Racikan
    {
        return DB::transaction(function () use ($data) {
            $racikan = Racikan::create($this->atribut($data));
            $this->buatBahan($racikan, $data['bahan'] ?? []);

            return $racikan;
        });
    }

    public function perbarui(Racikan $racikan, array $data): void
    {
        DB::transaction(function () use ($racikan, $data) {
            $racikan->update($this->atribut($data));
            $this->sinkronBahan($racikan, $data['bahan'] ?? []);
        });
    }

    public function hapus(Racikan $racikan): void
    {
        $racikan->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function atribut(array $data): array
    {
        return Arr::only($data, ['nama_menu', 'keterangan', 'aktif']);
    }

    /**
     * @return array<string, mixed>
     */
    private function atributBahan(array $baris): array
    {
        return Arr::only($baris, ['obat_id', 'jumlah', 'satuan_id']);
    }

    /**
     * Baris tanpa obat (sisa baris template) diabaikan.
     *
     * @return array<int, array<string, mixed>>
     */
    private function barisTerisi(array $bahan): array
    {
        return array_values(array_filter(
            $bahan,
            fn ($item) => trim((string) ($item['obat_id'] ?? '')) !== '',
        ));
    }

    private function buatBahan(Racikan $racikan, array $bahan): void
    {
        foreach ($this->barisTerisi($bahan) as $item) {
            $racikan->racikanBahan()->create($this->atributBahan($item));
        }
    }

    private function sinkronBahan(Racikan $racikan, array $bahan): void
    {
        $idDipakai = [];

        foreach ($this->barisTerisi($bahan) as $item) {
            $id = $item['id'] ?? null;
            // Cari lewat relasi agar id milik racikan lain tidak bisa diubah.
            $baris = $id ? $racikan->racikanBahan()->find($id) : null;
            $atribut = $this->atributBahan($item);

            if ($baris) {
                $baris->update($atribut);
            } else {
                $baris = $racikan->racikanBahan()->create($atribut);
            }

            $idDipakai[] = $baris->id;
        }

        // mtr_racikan_bahan tidak punya soft delete dan tidak direferensikan tabel lain.
        foreach ($racikan->racikanBahan()->whereNotIn('id', $idDipakai)->get() as $sisa) {
            $sisa->delete();
        }
    }
}
