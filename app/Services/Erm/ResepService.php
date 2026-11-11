<?php

namespace App\Services\Erm;

use App\Events\ResepDiterbitkan;
use App\Models\Erm\Pemeriksaan;
use App\Models\Erm\Resep;
use App\Models\Erm\ResepItem;
use App\Models\Erm\ResepRacikanBahan;
use App\Models\Master\Obat;
use App\Models\Master\Racikan;
use App\Models\Sys\LogAktivitas;
use App\Models\Sys\Pengaturan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ResepService
{
    public function buatResep(Pemeriksaan $pemeriksaan, User $oleh): Resep
    {
        return DB::transaction(function () use ($pemeriksaan, $oleh) {
            $tanggal = now()->format('Ymd');
            $penghitung = Pengaturan::firstOrCreate(
                ['kunci' => 'no_resep_terakhir'],
                ['nilai' => '0', 'keterangan' => 'Nomor resep terakhir', 'created_at' => now(), 'updated_at' => now()],
            );
            $penghitung = Pengaturan::where('kunci', 'no_resep_terakhir')->lockForUpdate()->first();
            [$tanggalTerakhir, $nomorTerakhir] = array_pad(explode(':', (string) $penghitung->nilai, 2), 2, '0');
            $nomor = $tanggalTerakhir === $tanggal ? ((int) $nomorTerakhir) + 1 : 1;
            $penghitung->update(['nilai' => "{$tanggal}:{$nomor}"]);

            $resep = Resep::create([
                'pemeriksaan_id' => $pemeriksaan->id,
                'dokter_id' => $pemeriksaan->dokter_id,
                'no_resep' => sprintf('RP-%s-%04d', $tanggal, $nomor),
                'status' => 'diterbitkan',
            ]);

            LogAktivitas::catat($oleh, 'terbit_resep', 'erm_resep', $resep->id);
            ResepDiterbitkan::dispatch($resep);

            return $resep->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function tambahItem(Resep $resep, array $data, User $oleh): ResepItem
    {
        if ($resep->status !== 'diterbitkan') {
            throw new \RuntimeException('Resep tidak dapat diubah setelah divalidasi.');
        }

        return DB::transaction(function () use ($resep, $data, $oleh) {
            $jenis = $data['jenis'] ?? 'reguler';
            $jumlah = (float) ($data['jumlah'] ?? 0);
            if ($jumlah <= 0) {
                throw new \InvalidArgumentException('Jumlah harus lebih dari 0.');
            }

            $dosis = $data['dosis'] ?? null;
            $satuanDosis = $data['satuan_dosis'] ?? null;
            $frekuensi = $data['frekuensi'] ?? null;
            $periode = $data['periode'] ?? null;
            $satuanPeriode = $data['satuan_periode'] ?? null;
            $kodeRute = $data['kode_rute'] ?? null;
            $namaRute = $data['nama_rute'] ?? null;
            $lamaHari = $data['lama_hari'] ?? null;
            $aturanTambahan = $data['aturan_tambahan'] ?? null;

            if ($jenis === 'reguler') {
                $obat = Obat::findOrFail($data['obat_id']);
                $item = ResepItem::create([
                    'resep_id' => $resep->id,
                    'jenis' => 'reguler',
                    'obat_id' => $obat->id,
                    'jumlah' => $jumlah,
                    'harga_satuan' => 0,
                    'aturan_pakai' => $data['aturan_pakai'] ?? $this->aturanPakai($dosis, $satuanDosis, $frekuensi, $periode, $satuanPeriode, $kodeRute, $lamaHari, $aturanTambahan),
                    'dosis' => $dosis,
                    'satuan_dosis' => $satuanDosis,
                    'frekuensi' => $frekuensi,
                    'periode' => $periode,
                    'satuan_periode' => $satuanPeriode,
                    'kode_rute' => $kodeRute,
                    'nama_rute' => $namaRute,
                    'lama_hari' => $lamaHari,
                    'aturan_tambahan' => $aturanTambahan,
                    'catatan' => $data['catatan'] ?? null,
                ]);
            } else {
                $racikan = ! empty($data['racikan_id']) ? Racikan::find($data['racikan_id']) : null;
                $item = ResepItem::create([
                    'resep_id' => $resep->id,
                    'jenis' => 'racikan',
                    'racikan_id' => $racikan?->id,
                    'nama_racikan' => $data['nama_racikan'] ?? $racikan?->nama,
                    'jumlah' => $jumlah,
                    'harga_satuan' => 0,
                    'biaya_jasa_racik' => 0,
                    'aturan_pakai' => $data['aturan_pakai'] ?? $this->aturanPakai($dosis, $satuanDosis, $frekuensi, $periode, $satuanPeriode, $kodeRute, $lamaHari, $aturanTambahan),
                    'dosis' => $dosis,
                    'satuan_dosis' => $satuanDosis,
                    'frekuensi' => $frekuensi,
                    'periode' => $periode,
                    'satuan_periode' => $satuanPeriode,
                    'kode_rute' => $kodeRute,
                    'nama_rute' => $namaRute,
                    'lama_hari' => $lamaHari,
                    'aturan_tambahan' => $aturanTambahan,
                    'catatan' => $data['catatan'] ?? null,
                ]);

                foreach (($data['bahan'] ?? []) as $bahan) {
                    ResepRacikanBahan::create([
                        'resep_item_id' => $item->id,
                        'obat_id' => $bahan['obat_id'],
                        'jumlah_bahan' => $bahan['jumlah_bahan'],
                        'satuan_id' => $bahan['satuan_id'],
                    ]);
                }
            }

            LogAktivitas::catat($oleh, 'tambah_item_resep', 'erm_resep_item', $item->id);

            return $item->fresh();
        });
    }

    public function aturanPakai(
        mixed $dosis,
        ?string $satuanDosis,
        mixed $frekuensi,
        mixed $periode,
        ?string $satuanPeriode,
        ?string $kodeRute,
        mixed $lamaHari,
        ?string $aturanTambahan,
    ): string {
        $bagian = [];
        if ($dosis !== null && $satuanDosis) {
            $bagian[] = "Dosis {$dosis} {$satuanDosis}";
        }
        if ($frekuensi && $periode && $satuanPeriode) {
            $bagian[] = "{$frekuensi}x per {$periode} {$satuanPeriode}";
        }
        if ($kodeRute) {
            $bagian[] = "rute {$kodeRute}";
        }
        if ($lamaHari) {
            $bagian[] = "selama {$lamaHari} hari";
        }
        if ($aturanTambahan) {
            $bagian[] = $aturanTambahan;
        }

        return implode(', ', $bagian);
    }

    public function salinRacikan(Resep $resep, Racikan $template, array $data, User $oleh): ResepItem
    {
        $data['jenis'] = 'racikan';
        $data['racikan_id'] = $template->id;
        $data['nama_racikan'] = $template->nama ?? $data['nama_racikan'] ?? null;
        $data['bahan'] = $template->racikanBahan->map(fn ($b) => [
            'obat_id' => $b->obat_id,
            'jumlah_bahan' => $b->jumlah,
            'satuan_id' => $b->satuan_id,
        ])->all();

        return $this->tambahItem($resep, $data, $oleh);
    }
}
