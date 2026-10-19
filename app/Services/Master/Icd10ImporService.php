<?php

namespace App\Services\Master;

use App\Models\Master\Icd10;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class Icd10ImporService
{
    private const UKURAN_BATCH = 500;

    private const MAKS_DILAPORKAN = 20;

    /**
     * Gabungkan isi CSV ke mtr_icd10: kode lama diperbarui, kode baru ditambah.
     *
     * @return array{ditambah: int, diperbarui: int, dilewati: int, rincianDilewati: array<int, array{baris: int, alasan: string}>}
     */
    public function impor(UploadedFile $berkas): array
    {
        ['data' => $data, 'dilewati' => $dilewati] = $this->bacaBerkas($berkas);

        $ringkasan = ['ditambah' => 0, 'diperbarui' => 0, 'dilewati' => count($dilewati)];

        DB::transaction(function () use ($data, &$ringkasan) {
            foreach (array_chunk($data, self::UKURAN_BATCH, true) as $potongan) {
                $sudahAda = Icd10::whereIn('kode', array_keys($potongan))->pluck('kode')->all();
                $ringkasan['diperbarui'] += count($sudahAda);
                $ringkasan['ditambah'] += count($potongan) - count($sudahAda);

                $baris = [];
                foreach ($potongan as $kode => $nama) {
                    $baris[] = ['kode' => $kode, 'nama_diagnosis' => $nama];
                }

                Icd10::upsert($baris, ['kode'], ['nama_diagnosis']);
            }
        });

        $ringkasan['rincianDilewati'] = array_slice($dilewati, 0, self::MAKS_DILAPORKAN);

        return $ringkasan;
    }

    /**
     * @return array{data: array<string, string>, dilewati: array<int, array{baris: int, alasan: string}>}
     */
    private function bacaBerkas(UploadedFile $berkas): array
    {
        // shortcut: nama diagnosis tidak boleh memuat baris baru, pindah ke fgetcsv bila suatu saat ada.
        $isi = (string) file_get_contents($berkas->getRealPath());
        $isi = (string) preg_replace('/^\xEF\xBB\xBF/', '', $isi);
        $baris = preg_split('/\r\n|\n|\r/', $isi) ?: [];
        $pemisah = $this->deteksiPemisah($baris);

        $data = [];
        $posisi = [];
        $dilewati = [];
        $pertama = true;

        foreach ($baris as $index => $teks) {
            $nomor = $index + 1;
            $teks = trim($teks);

            if ($teks === '') {
                continue;
            }

            $kolom = str_getcsv($teks, $pemisah);
            $kode = trim((string) ($kolom[0] ?? ''));
            $nama = trim((string) ($kolom[1] ?? ''));

            if ($pertama) {
                $pertama = false;
                if (in_array(strtolower($kode), ['kode', 'code'], true)) {
                    continue;
                }
            }

            if ($kode === '') {
                $dilewati[] = ['baris' => $nomor, 'alasan' => 'Kode kosong.'];
            } elseif (mb_strlen($kode) > 10) {
                $dilewati[] = ['baris' => $nomor, 'alasan' => 'Kode lebih dari 10 karakter.'];
            } elseif ($nama === '') {
                $dilewati[] = ['baris' => $nomor, 'alasan' => 'Nama diagnosis kosong.'];
            } elseif (mb_strlen($nama) > 255) {
                $dilewati[] = ['baris' => $nomor, 'alasan' => 'Nama diagnosis lebih dari 255 karakter.'];
            } else {
                if (isset($posisi[$kode])) {
                    $dilewati[] = ['baris' => $posisi[$kode], 'alasan' => "Kode dobel di file, dipakai baris {$nomor}."];
                }

                $posisi[$kode] = $nomor;
                $data[$kode] = $nama;
            }
        }

        return ['data' => $data, 'dilewati' => $dilewati];
    }

    /**
     * Pilih pemisah dari 10 baris pertama; seri dimenangkan titik-koma karena Excel Indonesia memakainya.
     *
     * @param  array<int, string>  $baris
     */
    private function deteksiPemisah(array $baris): string
    {
        $total = [';' => 0, "\t" => 0, ',' => 0];

        foreach (array_slice($baris, 0, 10) as $teks) {
            foreach ($total as $pemisah => $jumlah) {
                $total[$pemisah] = $jumlah + substr_count($teks, $pemisah);
            }
        }

        arsort($total);

        return array_key_first($total);
    }
}
