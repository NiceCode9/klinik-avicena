# Integrasi SATUSEHAT Kemenkes

> **Catatan penting**: dokumen ini menjelaskan *arsitektur & alur* di sisi aplikasi kita. Detail teknis API (URL, profil FHIR, kode terminologi, aturan validasi) HARUS dicocokkan dengan dokumentasi resmi SATUSEHAT/portal developer Kemenkes saat implementasi — jangan mengandalkan tebakan AI. Mulai dari **sandbox**.

## Tujuan & prinsip
- Kepatuhan: data kunjungan & diagnosis terkirim ke SATUSEHAT (PRD §4).
- **Simpan lokal dulu, kirim belakangan.** Request web tidak pernah menunggu API SATUSEHAT.
- Pengiriman **idempotent**: aman diulang tanpa menggandakan data di SATUSEHAT.
- Gangguan SATUSEHAT/internet tidak boleh mengganggu pelayanan pasien.

## Pemetaan data lokal → resource FHIR
| Lokal | Kolom penanda sinkron | FHIR | Catatan |
|---|---|---|---|
| Organisasi klinik | `.env SATUSEHAT_ORGANIZATION_ID` | Organization | didaftarkan di portal |
| `mtr_poli` | `ihs_location_id` | Location | satu per poli/unit |
| `mtr_dokter` | `ihs_practitioner_id` | Practitioner | dicari lewat NIK dokter |
| `mtr_pasien` | `ihs_number` | Patient | dicari lewat NIK pasien (via API), disimpan agar tidak dicari ulang |
| `reg_kunjungan` | `ihs_encounter_id` | Encounter | |
| `erm_diagnosis` | `ihs_condition_id` | Condition | memakai kode ICD-10 dari `mtr_icd10` |

Lab mandiri: praktisi pada Encounter = dokter lab yang dipilih (`lab_permintaan.dokter_id`).
Resource lain (Observation lab, Medication, dll.) di luar lingkup PRD v2.0; tambahkan lewat `decisions.md` bila diminta.

## Tabel
`sts_sinkronisasi` (satu baris per resource per referensi): `jenis_resource`, `referensi_tabel`, `referensi_id`, `status (menunggu|berhasil|gagal)`, `ihs_id`, `jumlah_percobaan`, `payload` (JSON), `respons` (JSON), `pesan_galat`, `terkirim_pada`.
`sts_token`: token akses terenkripsi + `kedaluwarsa_pada`. Tabel framework `jobs` & `failed_jobs` tetap dipakai.
Payload/respons berisi data pribadi → perlakukan sebagai data sensitif (jangan di-log, akses terbatas).

## Alur
1. Dokter/perawat klik **Simpan Data Medis** → `PemeriksaanService::simpan()` dalam transaksi: simpan data, buat baris `sts_sinkronisasi` (`menunggu`) untuk Encounter & Condition.
2. Setelah commit: `SatuSehatSyncJob::dispatch(...)->onQueue('satusehat')` (gunakan `afterCommit`).
3. Respons sukses ke layar dokter segera.
4. Worker (`queue:work --queue=satusehat,default`):
   1. Ambil token (refresh bila hampir kedaluwarsa, simpan di `sts_token`).
   2. Pastikan prasyarat: pasien punya `ihs_number` (cari via NIK bila kosong), dokter punya `ihs_practitioner_id`, poli punya `ihs_location_id`. Jika tidak bisa dipenuhi → status `gagal` dengan pesan jelas (mis. "NIK pasien tidak ditemukan di SATUSEHAT").
   3. Bentuk JSON FHIR → kirim **Encounter** → simpan `ihs_encounter_id`.
   4. Kirim **Condition** (merujuk Encounter) → simpan `ihs_condition_id`.
   5. Update `sts_sinkronisasi` → `berhasil`, `ihs_id`, `terkirim_pada`.
5. Jika Encounter sukses tetapi Condition gagal: retry **hanya** Condition (cek status per baris `sts_sinkronisasi`, jangan kirim ulang Encounter).

## Retry & error handling (PRD §4.2)
- `tries = 3`, `backoff = [60, 300, 900]` detik, antrean `satusehat`.
- Setelah 3 gagal → masuk `failed_jobs`, `sts_sinkronisasi.status = gagal`, notifikasi di dashboard admin.
- **[USULAN]** Klasifikasi: timeout/5xx/401 (setelah refresh token) → retry; 4xx validasi data → langsung `gagal` tanpa retry buta (pesan error dari SATUSEHAT disimpan agar bisa diperbaiki lalu Re-sync).
- Job memakai `ShouldBeUnique` per (jenis_resource, referensi) agar tidak dobel.
- **Manual Re-sync**: tombol di halaman SATUSEHAT (`satusehat.sinkronisasi`) → reset status ke `menunggu` + dispatch ulang. Mendukung aksi massal untuk semua `gagal`.

## Halaman Monitor (menu `satusehat.sinkronisasi`)
Ringkasan (menunggu/berhasil/gagal hari ini), tabel gagal dengan pesan galat, tombol Re-sync, filter tanggal/jenis. Badge jumlah gagal di dashboard admin.

## Pengujian
- Interface `SatuSehatClient` + implementasi `FakeSatuSehatClient` untuk tes (tidak memanggil jaringan).
- Tes: sukses, gagal sementara lalu sukses (retry), gagal permanen (masuk `failed_jobs`), Encounter ok/Condition gagal, idempotensi, prasyarat IHS belum ada.
- Uji end-to-end hanya di sandbox; kredensial produksi tidak pernah dipakai di tes.
