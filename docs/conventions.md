# Konvensi Kode — SIM Klinik Avicena

## Penamaan
| Hal | Aturan | Contoh |
|---|---|---|
| Tabel | `<prefix>_<nama_indonesia>` snake_case, tunggal | `reg_kunjungan`, `apt_stok_batch` |
| Kolom | snake_case Indonesia; FK `<tabel>_id` | `tanggal_kedaluwarsa`, `pasien_id` |
| Pengecualian kolom | `created_at`, `updated_at`, `deleted_at`, `password`, `remember_token` (bawaan Laravel/auth, Inggris) | |
| Model | StudlyCase tanpa prefix, di namespace modul | `App\Models\Registrasi\Kunjungan` |
| Relasi Eloquent | camelCase Indonesia; belongsTo dari nama FK | `$kunjungan->pasien`, `->antrean` |
| Controller | `<Entitas>Controller` di `Http/Controllers/<Ns>` | `Master\ObatController` |
| Service | `<Domain>Service` | `StokService`, `AntreanService` |
| Route name | kebab-case, `<entitas>.<aksi>` | `pasien.index`, `lab-mandiri.create` |
| URL | kebab-case Indonesia | `/pendaftaran/pasien`, `/apotek/obat-masuk` |
| View | `resources/views/<ns>/<entitas>/<aksi>.blade.php` | `master/obat/index.blade.php` |
| Kode menu | `<grup>.<fitur>` snake_case | `apotek.retur_supplier` |
| Enum status | huruf kecil snake_case Indonesia | `menunggu_bayar` |
| Istilah framework | tetap Inggris (Controller, Service, Policy, Job, Request) | |
| Pesan/label UI & validasi | Bahasa Indonesia (`lang/id`) | |

## Pola controller (tipis)
```php
public function store(SimpanKunjunganRequest $request, PendaftaranService $layanan)
{
    $kunjungan = $layanan->daftarkan($request->validated(), $request->user());
    return redirect()->route('kunjungan.show', $kunjungan)->with('sukses', 'Pasien berhasil didaftarkan.');
}
```
Tidak ada query kompleks/aturan bisnis di controller. Index: `paginate()` + eager loading.

## Pola Service
- Satu method publik = satu use case; nama kata kerja Indonesia (`daftarkan`, `ambilNomor`, `racik`, `kunci`, `bayar`).
- Bungkus perubahan multi-tabel dengan `DB::transaction()`. Lempar exception domain (`StokTidakCukupException`, `StatusTidakValidException`, `DataTerkunciException`) — jangan return `false`.
- Aturan yang bisa dites tanpa HTTP; inject dependensi lewat constructor.

Contoh nomor antrean (acuan pola; wajib ada tes konkurensi):
```php
public function ambilNomor(Poli $poli, Kunjungan $kunjungan): Antrean
{
    return DB::transaction(function () use ($poli, $kunjungan) {
        $tanggal = today();
        PenghitungAntrean::firstOrCreate(['poli_id' => $poli->id, 'tanggal' => $tanggal], ['nomor_terakhir' => 0]);
        $penghitung = PenghitungAntrean::where('poli_id', $poli->id)->where('tanggal', $tanggal)->lockForUpdate()->first();
        $penghitung->increment('nomor_terakhir');

        return Antrean::create([
            'kunjungan_id'  => $kunjungan->id,
            'poli_id'       => $poli->id,
            'tanggal'       => $tanggal,
            'nomor_urut'    => $penghitung->nomor_terakhir,
            'nomor_antrean' => sprintf('%s-%03d', $poli->kode, $penghitung->nomor_terakhir),
            'status'        => 'menunggu',
        ]);
    });
}
```

Kerangka FEFO (acuan pola):
```php
public function kurangi(Obat $obat, string|float $jumlah, string $jenisMutasi, Model $sumber): array
{
    return DB::transaction(function () use ($obat, $jumlah, $jenisMutasi, $sumber) {
        $sisa = $jumlah; $dipakai = [];
        $batch = StokBatch::where('obat_id', $obat->id)->where('jumlah_sisa', '>', 0)
            ->whereDate('tanggal_kedaluwarsa', '>=', today())
            ->orderBy('tanggal_kedaluwarsa')->orderBy('id')->lockForUpdate()->get();

        foreach ($batch as $b) {
            if ($sisa <= 0) break;
            $ambil = min($sisa, $b->jumlah_sisa);
            $b->decrement('jumlah_sisa', $ambil);
            MutasiStok::create([/* stok_batch_id, obat_id, jenis_mutasi, jumlah => -$ambil,
                                    saldo_setelah => $b->jumlah_sisa, referensi_tipe, referensi_id, dibuat_oleh */]);
            $dipakai[$b->id] = $ambil; $sisa -= $ambil;
        }
        if ($sisa > 0) throw new StokTidakCukupException($obat, $jumlah);   // rollback otomatis
        return $dipakai;
    });
}
```
(Gunakan `bcsub/bccomp` atau `brick/math` untuk desimal — jangan float.)

## Uang & angka
`DECIMAL(15,2)` untuk rupiah, `DECIMAL(12,2)` untuk jumlah obat. Hitung dengan bcmath/brick-math (string). Format tampilan `Rp 1.250.000` lewat helper `rupiah()`.

## Tanggal & waktu
Zona `Asia/Jakarta`. Gunakan Carbon. Tampilan `d-m-Y` / `d-m-Y H:i`. Hari antrean = `today()` zona aplikasi.

## Validasi & pesan
Selalu FormRequest. NIK: 16 digit angka. Pesan Indonesia (`lang/id/validation.php`).

## Blade & UI
- Layout AdminLTE 4 (`layouts.app`), flash `sukses/galat`, breadcrumb di `app-content-header`.
- Komponen Blade kecil & reusable (`<x-kartu>`, `<x-tabel>`, `<x-input-pasien>`); hindari logika di view.
- Tombol aksi dibungkus `@izin(...)`. Konfirmasi untuk aksi destruktif.
- Tabel besar: pagination server-side. Autocomplete (pasien, obat, ICD-10) lewat endpoint JSON ringan berizin.

## Pengujian
- PHPUnit/Pest + MySQL. Factory untuk model utama. `RefreshDatabase` + seeder minimal (peran, poli, menu).
- Prioritas tes: antrean, stok/FEFO/racikan/retur/opname, tagihan, penguncian, transisi status, izin, job SATUSEHAT (dengan fake client).
- Nama tes deskriptif Indonesia: `test_racikan_gagal_jika_satu_bahan_kurang_dan_tidak_memotong_stok_lain()`.

## Git
- Branch `fitur/<modul>-<ringkas>`; commit kecil, pesan imperatif Indonesia: `Tambah AntreanService dengan lock harian`.
- Satu PR/commit = satu kemampuan. Jangan campur refactor dan fitur.
- Jangan commit `.env`, dump DB berisi data pasien, atau file kredensial.

## Logging & audit
Gunakan `sys_log_aktivitas` untuk aksi bisnis (daftar, ubah, kunci, bayar, retur, opname, ubah hak akses). Log file hanya untuk error teknis tanpa data pasien.
