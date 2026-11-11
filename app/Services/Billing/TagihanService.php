<?php

namespace App\Services\Billing;

use App\Models\Apotek\PenjualanUmum;
use App\Models\Billing\Tagihan;
use App\Models\Billing\TagihanDetail;
use App\Models\Lab\PermintaanDetail;
use App\Models\Registrasi\Kunjungan;
use App\Models\Sys\Pengaturan;
use App\Services\Erm\Exceptions\DataTerkunciException;
use Illuminate\Support\Facades\DB;

/**
 * Pembentukan tagihan farmasi dan lab (D36/D37/D42, business-rules §9).
 * Idempoten: baris diperbarui lewat referensi_tipe + referensi_id.
 */
class TagihanService
{
    public function susunFarmasi(Kunjungan|PenjualanUmum $sumber): Tagihan
    {
        return DB::transaction(function () use ($sumber) {
            $sumber = $this->kunciSumber($sumber);
            $tagihan = $this->tagihan($sumber, 'farmasi');
            $this->pastikanBelumLunas($tagihan);

            if ($sumber instanceof Kunjungan && $sumber->jenis_kunjungan === 'poli') {
                $pemeriksaan = $sumber->pemeriksaan()->orderByDesc('id')->first();

                if ($pemeriksaan !== null) {
                    $this->simpanDetail($tagihan, [
                        'komponen' => 'konsultasi',
                        'referensi_tipe' => 'erm_pemeriksaan',
                        'referensi_id' => $pemeriksaan->id,
                        'deskripsi' => 'Biaya periksa',
                        'harga_satuan' => $pemeriksaan->tarif_konsultasi ?? '0',
                    ]);
                }

                $this->detailSekali($tagihan, 'uang_kopi', 'Uang kopi', $this->nilaiPengaturan('uang_kopi'));
                $this->detailSekali($tagihan, 'simpanan', 'Simpanan', $this->nilaiPengaturan('simpanan_default'));
            }

            $this->hitungTotal($tagihan);

            return $tagihan->refresh();
        });
    }

    /**
     * Tagihan lab dibuat hanya setelah semua permintaan lab kunjungan terkunci (§7).
     */
    public function susunLab(Kunjungan $kunjungan): ?Tagihan
    {
        return DB::transaction(function () use ($kunjungan) {
            $terkunci = Kunjungan::whereKey($kunjungan->id)->lockForUpdate()->firstOrFail();

            $permintaan = $terkunci->permintaan()
                ->where('status', '!=', 'batal')
                ->lockForUpdate()
                ->get(['id', 'dikunci', 'status']);

            if ($permintaan->isEmpty() || $permintaan->contains(fn ($baris) => ! $baris->dikunci)) {
                return null;
            }

            $tagihan = $this->tagihan($terkunci, 'lab');
            // shortcut: permintaan lab baru setelah tagihan lab lunas ditolak; dukung tagihan tambahan bila alur itu dibutuhkan.
            $this->pastikanBelumLunas($tagihan);

            $detail = PermintaanDetail::whereHas('permintaan', function ($query) use ($terkunci) {
                $query->where('kunjungan_id', $terkunci->id)->where('status', '!=', 'batal');
            })->with('pemeriksaanLab')->get();

            foreach ($detail as $baris) {
                $this->simpanDetail($tagihan, [
                    'komponen' => 'lab',
                    'referensi_tipe' => 'lab_permintaan_detail',
                    'referensi_id' => $baris->id,
                    'deskripsi' => $baris->pemeriksaanLab?->nama ?? 'Pemeriksaan lab',
                    'harga_satuan' => $baris->tarif,
                ]);
            }

            $this->hitungTotal($tagihan);

            return $tagihan->refresh();
        });
    }

    /**
     * @param  array{komponen: string, referensi_tipe?: ?string, referensi_id?: ?int, deskripsi: string, jumlah?: int|float|string, harga_satuan: int|float|string}  $data
     */
    public function tambahDetail(Tagihan $tagihan, array $data): TagihanDetail
    {
        return DB::transaction(function () use ($tagihan, $data) {
            $terkunci = Tagihan::whereKey($tagihan->id)->lockForUpdate()->firstOrFail();
            $this->pastikanBelumLunas($terkunci);

            $detail = $this->simpanDetail($terkunci, $data);
            $this->hitungTotal($terkunci);

            return $detail->refresh();
        });
    }

    public function lunas(Tagihan $tagihan): bool
    {
        if ($tagihan->status === 'lunas') {
            return true;
        }

        $dibayar = $tagihan->pembayaran()->sum('jumlah_bayar') ?: '0';

        return bccomp((string) $dibayar, (string) $tagihan->total, 2) >= 0;
    }

    private function kunciSumber(Kunjungan|PenjualanUmum $sumber): Kunjungan|PenjualanUmum
    {
        $kelas = $sumber::class;

        return $kelas::whereKey($sumber->id)->lockForUpdate()->firstOrFail();
    }

    private function tagihan(Kunjungan|PenjualanUmum $sumber, string $jenis): Tagihan
    {
        $kunci = $sumber instanceof Kunjungan
            ? ['kunjungan_id' => $sumber->id, 'jenis' => $jenis]
            : ['penjualan_umum_id' => $sumber->id, 'jenis' => $jenis];

        $tagihan = Tagihan::where($kunci)->lockForUpdate()->first();

        return $tagihan ?? Tagihan::create($kunci + [
            'no_tagihan' => $this->nomorTagihan(),
            'subtotal' => '0',
            'diskon' => '0',
            'total' => '0',
            'status' => 'belum_bayar',
        ]);
    }

    /**
     * @param  array{komponen: string, referensi_tipe?: ?string, referensi_id?: ?int, deskripsi: string, jumlah?: int|float|string, harga_satuan: int|float|string}  $data
     */
    private function simpanDetail(Tagihan $tagihan, array $data): TagihanDetail
    {
        $jumlah = (string) ($data['jumlah'] ?? '1');
        $harga = (string) $data['harga_satuan'];

        return TagihanDetail::updateOrCreate(
            [
                'tagihan_id' => $tagihan->id,
                'komponen' => $data['komponen'],
                'referensi_tipe' => $data['referensi_tipe'] ?? null,
                'referensi_id' => $data['referensi_id'] ?? null,
            ],
            [
                'deskripsi' => $data['deskripsi'],
                'jumlah' => $jumlah,
                'harga_satuan' => bcadd($harga, '0', 2),
                'subtotal' => bcmul($jumlah, $harga, 2),
            ],
        );
    }

    private function detailSekali(Tagihan $tagihan, string $komponen, string $deskripsi, string $harga): void
    {
        TagihanDetail::firstOrCreate(
            ['tagihan_id' => $tagihan->id, 'komponen' => $komponen],
            [
                'referensi_tipe' => 'tagihan',
                'referensi_id' => $tagihan->id,
                'deskripsi' => $deskripsi,
                'jumlah' => '1',
                'harga_satuan' => bcadd($harga, '0', 2),
                'subtotal' => bcadd($harga, '0', 2),
            ],
        );
    }

    private function hitungTotal(Tagihan $tagihan): void
    {
        $subtotal = bcadd((string) $tagihan->tagihanDetail()->sum('subtotal'), '0', 2);
        $total = bcsub($subtotal, (string) $tagihan->diskon, 2);

        $tagihan->update(['subtotal' => $subtotal, 'total' => $total]);
    }

    private function pastikanBelumLunas(Tagihan $tagihan): void
    {
        // Tagihan kosong bertotal 0 tidak dianggap terkunci sampai ada pembayaran nyata.
        if ($tagihan->status === 'lunas' || $tagihan->pembayaran()->exists()) {
            throw new DataTerkunciException('Tagihan yang sudah lunas tidak dapat disusun ulang.');
        }
    }

    private function nilaiPengaturan(string $kunci): string
    {
        $nilai = Pengaturan::where('kunci', $kunci)->value('nilai');

        return $nilai === null ? '0' : (string) $nilai;
    }

    private function nomorTagihan(): string
    {
        $tanggal = now()->format('Ymd');

        Pengaturan::firstOrCreate(
            ['kunci' => 'no_tagihan_terakhir'],
            ['nilai' => '0', 'keterangan' => 'Nomor tagihan terakhir', 'created_at' => now(), 'updated_at' => now()],
        );
        $penghitung = Pengaturan::where('kunci', 'no_tagihan_terakhir')->lockForUpdate()->first();
        [$tanggalTerakhir, $nomorTerakhir] = array_pad(explode(':', (string) $penghitung->nilai, 2), 2, '0');
        $nomor = $tanggalTerakhir === $tanggal ? ((int) $nomorTerakhir) + 1 : 1;
        $penghitung->update(['nilai' => "{$tanggal}:{$nomor}"]);

        return sprintf('TG-%s-%04d', $tanggal, $nomor);
    }
}
