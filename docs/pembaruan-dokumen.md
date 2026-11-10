# Pembaruan Dokumen — Pemetaan SATUSEHAT (tempel ke dokumen yang sudah ada)

> **ARSIP (30 Okt 2026):** ini catatan historis paket D14–D20. **Jangan menempel ulang isinya.** Angka jumlah tabel, roadmap Fase 7, dan prompt Condition/Medication/DiagnosticReport di bawah sudah digantikan oleh `decisions.md` (D33, D34–D43), `satusehat.md`, `prompts-fase7.md`, dan `roadmap.md`.

Dokumen Anda mungkin sudah Anda sunting, jadi perubahan di bawah diberikan sebagai potongan yang bisa ditempel. `satusehat.md` dan `satusehat-kebutuhan-data.md` di paket ini **menggantikan** versi lama pada saat paket dibuat; ganti seluruh file hanya jika file Anda masih versi lama. Setelah menempel, jalankan **P-DOKUMEN** dari `prompts.md` agar AI memeriksa konsistensinya.

> **Status paket:** ini paket awal D14–D20. Jangan mengganti seluruh file docs yang sudah lebih baru, dan jangan menimpa D21–D22 (serta Q24 yang terjawab di D22) yang sudah ada di `docs/decisions.md`. Sumber kebenaran tetap `docs/decisions.md`, `docs/business-rules.md`, dan `docs/database.md`.

## 1. `AGENTS.md`
- Di "Dokumen rujukan" tambahkan: `docs/satusehat-kebutuhan-data.md` · `docs/prompts.md` · `docs/prompts-fase7.md`.
- Tambahkan aturan 13: **"Payload SATUSEHAT hanya dari dokumentasi resmi yang dibaca saat implementasi (dicatat di `docs/satusehat-payload-<resource>.md` + sumbernya). Jangan menebak elemen wajib, kode terminologi, atau URL. Jangan memakai PDF playbook lama."**
- Ubah aturan 10: "SATUSEHAT: layanan domain memancarkan **event** `afterCommit`; listener SATUSEHAT membuat `sts_sinkronisasi` + job. Jangan panggil API SATUSEHAT di request web."

## 2. `docs/decisions.md`
Ganti **D14** dan tambahkan D15–D20; tambahkan Q15–Q22. **Jangan menimpa D21–D22/Q24** yang sudah ada di `docs/decisions.md`.

| # | Keputusan | Alasan |
|---|---|---|
| D14 | Layanan domain memancarkan event `afterCommit`: `KunjunganDidaftarkan`, `TtvDisimpan`, `PemeriksaanDimulai`, `PemeriksaanSelesai`, `ResepDiterbitkan`, `ResepDiserahkan`, `LabDiminta`, `SpesimenDiambil`, `LabDikunci`. Listener SATUSEHAT (Fase 7) membuat `sts_sinkronisasi` + job. | Encounter punya siklus (arrived → in-progress → finished); modul klinis bisa selesai tanpa SATUSEHAT |
| D15 | Cakupan SATUSEHAT: prasyarat; Tahap A Encounter+Condition; B MedicationRequest/Dispense/Medication; C ServiceRequest/Specimen/Observation lab + Observation TTV; D Composition. DiagnosticReport menunggu verifikasi. Procedure/alergi/ClinicalImpression ditunda. | Akun SATUSEHAT klinik + analisis kebutuhan data |
| D16 | Observation TTV ikut dikirim. | Data sudah dikumpulkan; pelengkap resume medis |
| D17 | Spesimen dicatat di tabel `lab_spesimen`; `lab_permintaan_detail.spesimen_id` menautkan. | `Specimen.type` (SNOMED) wajib |
| D18 | Tenaga kesehatan non-dokter di `mtr_nakes` (nik terenkripsi, `ihs_practitioner_id`, `pengguna_id` opsional). | Apoteker/perawat/petugas lab butuh IHS Practitioner; akun login terpisah dari data nakes |
| D19 | Lokasi non-poli (apotek) di `mtr_lokasi` (seed `APT` lewat `PemetaanSatuSehatSeeder`). | Lokasi dispense obat |
| D20 | `ihs_id` resource baru disimpan di `sts_sinkronisasi` (kolom `aksi`, `sub_kunci`, `bergantung_pada_id`); bukan kolom baru di tabel domain. | Menghindari kolom berlebih; mendukung ketergantungan & banyak resource per referensi |

Pertanyaan baru: **Q15–Q20 = terjawab** (lihat §6 `satusehat-kebutuhan-data.md`). **Q21** DiagnosticReport perlu atau tidak (default: tunda sampai halaman resmi dibaca). **Q22** section wajib & kode `Composition` (default: ikuti halaman resmi saat Tahap D). **Q23** kapan Encounter `in-progress` dikirim bila dokter lupa menekan "mulai" (default: `PemeriksaanDimulai` dipicu otomatis saat dokter membuka form pemeriksaan pertama kali). **Q24** terjawab di D22.

## 3. `docs/roadmap.md` — ganti seluruh "Fase 7" dengan:
### Fase 7 — Integrasi SATUSEHAT (dipecah)
| Sub-fase | Isi | Ukuran |
|---|---|---|
| **7.0 Pemetaan terminologi** | kolom pemetaan di form master (obat→KFA, pemeriksaan/parameter lab→LOINC/SNOMED/UCUM, poli→tipe pelayanan), CRUD `mtr_nakes` & `mtr_lokasi`, laporan "master belum terpetakan" | L |
| **7A Fondasi + Tahap A** | client/token, sinkron IHS master, siklus Encounter, Condition, listener & ketergantungan | XL |
| **7B Obat** | Medication/MedicationRequest/MedicationDispense | L |
| **7C Lab + TTV** | ServiceRequest, Specimen, Observation lab, Observation TTV (+ DiagnosticReport bila diputuskan) | XL |
| **7D Resume medis** | Composition | M |
| **7E Monitor & uji sandbox** | monitor, Re-sync, rasio Composition:Encounter, checklist sandbox | M |

Catatan lintas fase (kerjakan di fase asalnya, bukan menunggu Fase 7): **Fase 2** mencatat `waktu_datang` + riwayat status; **Fase 3** form resep dosis terstruktur + `waktu_mulai/selesai` + cara keluar; **Fase 4** pencatatan spesimen; **Fase 5** event `ResepDiserahkan`. Lihat `prompts.md` (tambahan sudah disisipkan di 2.3, 3.1, 3.6, 3.7, 4.1–4.3, dan 5.4).

## 4. `docs/database.md` — tambahkan
Migration `2026_10_21_000001_tambah_pemetaan_dan_kebutuhan_data_satusehat` menambah (total **60 tabel**):
- **Tabel baru**: `mtr_nakes`, `mtr_lokasi`, `reg_kunjungan_riwayat_status`, `lab_spesimen`.
- **Kolom baru**: `mtr_obat` (`kode_kfa_virtual`, `nama_kfa`, `kode_bentuk_sediaan`, `nama_bentuk_sediaan`; kolom lama `kode_kfa` = kode produk **aktual**); `mtr_pemeriksaan_lab` (`kode_loinc`, `nama_loinc`, `kode_spesimen`, `nama_spesimen`); `mtr_parameter_lab` (`kode_loinc`, `nama_loinc`, `satuan_ucum`, `jenis_nilai`); `mtr_poli` (`kode_tipe_pelayanan`, `nama_tipe_pelayanan`); `reg_kunjungan` (`waktu_datang`, `waktu_mulai`, `waktu_selesai`, `kode_cara_keluar`, `nama_cara_keluar`); `erm_resep_item` (`dosis`, `satuan_dosis`, `frekuensi`, `periode`, `satuan_periode`, `kode_rute`, `nama_rute`, `lama_hari`, `aturan_tambahan`); `lab_permintaan_detail.spesimen_id`; `sts_sinkronisasi` (`aksi`, `sub_kunci`, `bergantung_pada_id`, unik `(jenis_resource, aksi, referensi_tabel, referensi_id, sub_kunci)`).
- Kolom sensitif baru: `mtr_nakes.nik` (encrypted), `mtr_nakes.nik_hash` (HMAC).
- Aturan app-level baru: kolom pemetaan boleh kosong, tetapi item belum terpetakan **tidak boleh dikirim** (status `gagal` dengan pesan jelas); satu `ServiceRequest` per kode pemeriksaan; `Specimen.type` wajib sebelum kirim lab.
- Skrip DDL setara: `docs/schema-delta-satusehat.sql` (sudah diuji pada MariaDB 10.11 di atas schema dasar).

## 5. `docs/business-rules.md` — tambahkan
- **§1 Status**: setiap perpindahan status mencatat baris `reg_kunjungan_riwayat_status` (status, waktu, pengguna). `waktu_datang` terisi saat dibuat, `waktu_mulai` saat `diperiksa`/mulai dilayani, `waktu_selesai` saat selesai pemeriksaan. Cara keluar (`kode_cara_keluar`) wajib diisi saat pemeriksaan difinalkan.
- **§6 e-Resep**: item wajib memuat dosis terstruktur (`dosis`, `satuan_dosis`, `frekuensi`, `periode`, `satuan_periode`, `kode_rute`, `lama_hari`); `aturan_pakai` teks tetap disimpan untuk tampilan. Obat tanpa kode KFA boleh diresepkan tetapi akan ditandai "belum terpetakan" dan tidak terkirim ke SATUSEHAT.
- **§7 Lab**: pengambilan spesimen dicatat di `lab_spesimen` (jenis dari `mtr_pemeriksaan_lab.kode_spesimen`, waktu, petugas) sebelum hasil diinput; `lab_permintaan_detail.spesimen_id` terisi. Status spesimen: `menunggu_diambil → diambil → diterima | ditolak`; hasil hanya setelah `diterima`; `ditolak` wajib `catatan` (D22). Permintaan dipecah menjadi **satu baris detail per kode pemeriksaan** (sesuai satu ServiceRequest per kode).
- **§11 (baru) Event SATUSEHAT**: daftar event pada D14; event dipancarkan hanya setelah transaksi commit.

## 6. Menu & hak akses (tambahkan ke `config/menu.php`, lalu `php artisan menu:sinkron`)
```php
// di grup 'master'
['kode' => 'master.nakes',  'nama' => 'Tenaga Kesehatan', 'rute' => 'nakes.index'],
['kode' => 'master.lokasi', 'nama' => 'Lokasi',           'rute' => 'lokasi.index'],
// di grup 'lab'
['kode' => 'lab.spesimen',  'nama' => 'Pengambilan Spesimen', 'rute' => 'spesimen.index'],
// di grup 'satusehat'
['kode' => 'satusehat.pemetaan', 'nama' => 'Pemetaan Terminologi', 'rute' => 'satusehat-pemetaan.index'],
```
Hak awal (tambahkan di `HakAksesAwalSeeder`): `petugas_lab`: `lab.spesimen` = `LTU`. Menu `master.nakes`, `master.lokasi`, `satusehat.*` hanya admin (otomatis lewat `menu:sinkron`).

## 7. Cara memasang paket
1. Salin isi `database/`, `app/` ke project; `docs/` ke folder docs.
2. `php artisan migrate` lalu `php artisan db:seed --class=PemetaanSatuSehatSeeder`.
3. Tambahkan relasi/cast ke model yang sudah ada (jalankan **prompt 7.0a** di `prompts-fase7.md`): `Kunjungan::riwayatStatus()`, `Lab\Permintaan::spesimen()`, `Lab\PermintaanDetail::spesimen()`, `SatuSehat\Sinkronisasi::induk()/dependen()`, `App\Models\User::nakes()`, serta cast tanggal/desimal untuk kolom baru.
4. Tempel potongan dokumen di atas.
