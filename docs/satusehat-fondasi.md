# Fondasi SATUSEHAT: rancangan sebelum koding

Status: **disetujui 22 Oktober 2026** (D23–D27). Belum ada package yang dipasang dan belum ada kode yang ditulis.
Tanggal: 22 Oktober 2026.
Rujukan: `satusehat.md`, `satusehat-kebutuhan-data.md`, `decisions.md` (D14–D27), `prompts-fase7.md` (7.0a–7E.2).

Dokumen ini mengunci batas paket, lapisan, kontrak, alur, pemetaan terminologi, strategi snapshot, dan strategi tes. Keputusan §5 sudah dicatat sebagai D23–D27 di `decisions.md`.

## 1. Batas paket `ivanwilliammd/satusehat-integration`

| Dipakai | Tidak dipakai |
|---|---|
| Builder payload FHIR generik (Patient, Practitioner, Organization, Location, Encounter) lewat satu file `App\Services\SatuSehat\PembentukFhir` | Queue/scheduler, tabel token, tabel log, retry internal, klien `terminology.ivanmd.id`, HTTP client bawaan |

- Versi dipin persis (`4.14.1`), bukan `^4`.
- `PembentukFhir` satu-satunya file yang boleh menyentuh namespace paket. Service domain, job, dan payload builder tidak pernah mengimpornya.
- Saat install: jangan publish migration, matikan logging library. Kalau service provider-nya mendaftarkan scheduler/command, masukkan ke `extra.laravel.dont-discover` dan daftarkan manual hanya yang dipakai.
- Kalau builder paket tidak bisa memenuhi elemen wajib, `PembentukFhir` menyusun array sendiri. Paket alat bantu, bukan sumber kebenaran.
- Konsekuensi yang diterima: kalau ternyata seluruh payload klinis disusun sendiri, `PembentukFhir` menjadi pembungkus tipis. Karena hanya satu file, paket bisa dilepas tanpa menyentuh domain.

## 2. Lapisan

```
Event domain (afterCommit)
  └─ Listener tipis
       └─ SatuSehat\SinkronisasiService ──► sts_sinkronisasi
                                              └─ SatuSehatSyncJob (queue satusehat)
                                                   ├─ cek induk
                                                   ├─ *PayloadBuilder  (array murni)
                                                   ├─ ValidasiPayload  (config wajib)
                                                   └─ SatuSehatClient ──► HTTP / Fake
```

| Komponen | Tanggung jawab |
|---|---|
| `SatuSehatClient` (interface) | `post()`, `put()`, `get()`; selalu mengembalikan `HasilKirim` |
| `HasilKirim` (readonly) | `berhasil`, `ihsId`, `statusHttp`, `body`, `pesan`, `klasifikasi` (`Berhasil｜Sementara｜Permanen`) |
| `HttpSatuSehatClient` | Laravel `Http`; token dari `TokenSatuSehatService`; tanpa retry |
| `FakeSatuSehatClient` | Mencatat panggilan, respons siap pakai, mode gagal untuk tes |
| `SatuSehatSyncJob` | Orkestrasi saja: induk, builder, validasi, simpan hasil |
| `*PayloadBuilder` | `bangun(Sinkronisasi $baris): array`; tanpa tulis DB, tanpa HTTP |
| `ValidasiPayload` | Memeriksa path wajib per resource dari config |
| `PemetaanService` | Laporan master belum lengkap + cek prasyarat; dipakai ulang oleh job |

Aturan tambahan:

- Listener dibungkus try/catch. Kegagalan SATUSEHAT tidak boleh membatalkan transaksi klinis yang sudah commit, dan tidak boleh menulis NIK, hasil lab, atau isi SOAP ke log.
- Token disimpan di `sts_token` dengan cast `encrypted`, refresh sebelum kedaluwarsa. Kredensial hanya dari `.env`.
- Endpoint, elemen wajib, dan kode terminologi tidak ditulis dari ingatan. Tiap resource punya `docs/satusehat-payload-<resource>.md` sebelum builder-nya diisi.

## 3. Alur satu baris sinkronisasi

1. Service domain commit, lalu memancarkan event.
2. Listener memanggil `SinkronisasiService::buat()`. Baris unik per `(jenis_resource, aksi, referensi, sub_kunci)`, jadi event ganda tidak menggandakan resource.
3. Job memeriksa induk: `berhasil` lanjut, `menunggu/diproses` → `release()`, `gagal` → anak ditandai `gagal` dengan pesan "induk gagal" (tidak menunggu selamanya).
4. Payload builder menyusun array. Prasyarat kurang (KFA/LOINC/SNOMED/IHS kosong) → `Permanen` sebelum HTTP, pesan menyebut field dan referensi.
5. `ValidasiPayload` memeriksa elemen wajib. Config resource kosong berarti resource belum boleh diimplementasikan, bukan lolos apa adanya.
6. Kirim. Sukses simpan `ihs_id`, `respons`, `terkirim_pada`, status `berhasil`.
7. Setelah induk berhasil, dispatch anak yang masih `menunggu`.
8. Re-sync manual: status kembali `menunggu`; kalau `ihs_id` sudah ada, aksi dipaksa `PUT`. Tanpa menggandakan resource.

## 4. Idempotensi dan snapshot

- Setiap payload selalu memuat `identifier` lokal (mis. `no_kunjungan`) supaya resource bisa direkonsiliasi kalau POST timeout padahal server sudah membuatnya. Perilaku pencarian ini `TODO(verifikasi-resmi)` sampai halaman resmi dibaca.
- `payload` disimpan per percobaan di `sts_sinkronisasi`, jadi isi yang benar-benar dikirim bisa diaudit.
- Pemetaan master dibaca saat kirim. Kalau master diubah di antara event dan kirim (jarang, hitungan menit), payload memakai kode baru. Ini diterima; yang di-snapshot tetap harga/tarif sesuai D10.
- `ihs_id` resource baru disimpan di `sts_sinkronisasi` (D20), bukan kolom baru di tabel domain. Pengecualian yang sudah ada: `reg_kunjungan.ihs_encounter_id` dan `erm_diagnosis.ihs_condition_id`.

## 5. Pemetaan terminologi

Tiga tingkat, jangan dicampur:

1. **Referensi** (`mtr_terminologi`): `sistem` (`kfa|loinc|snomed|ucum`), `kode`, `display`, `metadata` JSON, `aktif`, unik per `(sistem, kode)`. Diisi perintah impor untuk subset LOINC/SNOMED/UCUM dan sinkron KFA lewat API resmi (cache, rate limit, kredensial hanya di server).
2. **Master**: kolom yang sudah ada (`mtr_obat.kode_kfa*`, lab, poli, nakes, lokasi). Manusia memilih lewat pencarian; saat simpan, kode divalidasi harus ada di referensi. Tidak ada auto-map, terutama obat. Saran boleh, pemilihan tetap manusia.
3. **Transaksi**: kode dan nama yang dipilih ikut tersimpan di payload sinkronisasi.

Bertahap: 7.0c laporan "belum terpetakan" tetap bisa jalan dengan input manual. Picker + importer menjadi 7.0d, setelah laporan. Satu tabel referensi, bukan tabel per sistem. `mtr_terminologi` bukan sumber kebenaran transaksi; master tetap menyimpan kode dan nama yang dipilih.

### 5.1 Keputusan (D24–D27)

| # | Pertanyaan | Keputusan |
|---|---|---|
| K1 | KFA aktual vs virtual | `kode_kfa` = aktual (93xxxxxx) untuk MedicationDispense; `kode_kfa_virtual` untuk MedicationRequest **hanya jika** halaman resmi mengonfirmasi. Tanpa fallback diam-diam. Laporan punya dua status terpisah, bukan satu. |
| K2 | Satuan dosis UCUM | Tambah `mtr_satuan.kode_ucum` (nullable, migration baru). `erm_resep_item.satuan_dosis` tetap nama satuan pilihan dari master; builder me-resolve ke UCUM. UCUM kosong → gagal permanen, tidak menebak `{nama}`. `frekuensi/periode/satuan_periode` dipetakan ke `Timing.repeat`. |
| K3 | KFA di master atau batch | Master (`mtr_obat`). KFA adalah identitas produk, batch hanya untuk FEFO/kedaluwarsa. Batch tidak menyimpan KFA. |
| K4 | Composition saat sebagian induk gagal | Tunggu selama ada induk `menunggu/diproses`; kalau induk `gagal`, Composition tetap dikirim dengan resource yang tersedia dan alasannya dicatat. Detail final saat 7D.1 membaca halaman resmi. |

## 6. Retry dan klasifikasi galat

| Kondisi | Tindakan |
|---|---|
| 401 | Refresh token sekali, tidak dihitung percobaan |
| 408/429/5xx/timeout | `Sementara`, retry `[60, 300, 900]` |
| 400/422 | `Permanen`, pesan validasi disimpan |
| 409/duplikat | Rekonsiliasi ID lalu `PUT` (`TODO(verifikasi-resmi)`) |
| Prasyarat belum dipetakan | `Permanen` sebelum HTTP |
| Induk gagal | Anak `Permanen`, pesan jelas |

Gagal permanen tidak menghapus data. Baris tetap ada untuk Re-sync setelah master diperbaiki.

## 7. Tes

- Unit: tiap payload builder dari fixture domain, assert field kunci (bukan seluruh JSON).
- Unit: `ValidasiPayload` menolak path wajib yang hilang, pesannya menyebut field.
- Feature: event → baris `sts_sinkronisasi`; urutan induk; `release()` saat induk belum berhasil; induk gagal → anak gagal; retry vs permanen; re-sync memakai `PUT`; tidak ada panggilan jaringan.
- Feature: transaksi klinis tetap sukses saat SATUSEHAT mati.
- Mapping: tiap kategori laporan, item lengkap tidak muncul, simpan kode tak dikenal ditolak.
- Konversi waktu WIB → UTC dan batas 3 Juni 2014.
- Tidak ada tes yang memanggil API asli; uji sandbox tetap manual (7E.2).

## 8. Yang sengaja tidak dibangun sekarang

- Picker KFA/LOINC di 7.0a–7.0c dan auto-map obat.
- Mirror seluruh dataset terminologi.
- Tabel queue/token/log milik paket.
- DiagnosticReport.
- Validasi profil FHIR penuh di luar config wajib.

## 9. Langkah setelah persetujuan

1. Sudah dicatat sebagai D23–D27 di `decisions.md`.
2. Jalankan 7.0a (relasi/cast model), lalu 7.0b dan 7.0c.
3. 7.0d (picker + importer) dikerjakan setelah laporan pemetaan terbukti dipakai.
