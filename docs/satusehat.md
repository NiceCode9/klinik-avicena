# Integrasi SATUSEHAT Kemenkes (menggantikan versi sebelumnya)

> **Aturan emas**: dokumen ini menjelaskan _arsitektur & alur_ di sisi aplikasi kita. Detail API (URL, profil FHIR, elemen wajib, kode terminologi) HARUS dibaca dari dokumentasi resmi `satusehat.kemkes.go.id/platform/docs` saat mengimplementasikan tiap resource, lalu dicatat di `docs/satusehat-payload-<resource>.md` beserta sumbernya. **Jangan menebak. Jangan memakai PDF playbook lama** (mis. kode `Composition.type` berubah antar versi). Mulai dari **sandbox**.
> Analisis kebutuhan data dan celah skema: `satusehat-kebutuhan-data.md`.

## 1. Cakupan (diputuskan)

Klinik dengan ERM, e-resep, dan laboratorium. Akun SATUSEHAT klinik menampilkan beberapa resource, tetapi **cakupan yang dikirim dibatasi oleh D33 (29 Okt 2026)**: hanya Encounter, Observation (TTV dan hasil lab), Composition, ServiceRequest, dan Specimen. Condition dan Medication* tidak dikirim sampai ada keputusan baru.

| Tahap                        | Resource                                                                      | Prasyarat/Catatan                                                                                    |
| ---------------------------- | ----------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------- |
| **0 (prasyarat)**            | Organization, Location, Practitioner, Patient                                 | IHS di `mtr_poli`, `mtr_lokasi`, `mtr_dokter`, `mtr_nakes`, `mtr_pasien`                             |
| **A**                        | Encounter (siklus)                                                            | inti; Condition **tidak** dikirim (D33)                                                              |
| **B**                        | — (di luar cakupan)                                                           | Medication, MedicationRequest, MedicationDispense tidak dikirim (D33)                                |
| **C**                        | ServiceRequest, Specimen, Observation (hasil lab), Observation (TTV)          | butuh LOINC, SNOMED spesimen, UCUM                                                                   |
| **C2**                       | — (di luar cakupan)                                                           | DiagnosticReport tidak dikirim (D33)                                                                 |
| **D**                        | Composition (resume medis rawat jalan)                                        | dikirim terakhir; mereferensikan resource yang tersedia                                              |
| **Tunda**                    | Condition, Medication*, DiagnosticReport, Procedure, AllergyIntolerance, ClinicalImpression, CarePlan | di luar cakupan sampai ada keputusan baru                                                            |
| **Tidak relevan**            | ImagingStudy, MedicationAdministration, dll.                                  | tidak ada layanan terkait                                                                            |

## 2. Prinsip

1. **Simpan lokal dulu, kirim belakangan.** Request web tidak menunggu API.
2. **Idempotent & bisa di-PUT.** Setiap resource yang berhasil menyimpan `ihs_id`; pembaruan memakai ID itu.
3. **Event, bukan panggilan langsung.** Service domain memancarkan event `afterCommit`; listener SATUSEHAT membuat baris `sts_sinkronisasi` + job. ERM/Lab/Apotek bisa selesai dan dites tanpa SATUSEHAT.
4. **Urutan ditentukan ketergantungan** (`sts_sinkronisasi.bergantung_pada_id`).
5. **Waktu dikirim UTC+00** (WIB dikurangi 7 jam); tanggal tidak boleh sebelum 3 Juni 2014 (aturan dokumentasi resmi).

## 3. Siklus Encounter

| Momen di aplikasi                 | Event                  | Aksi SATUSEHAT                                                           | Encounter.status |
| --------------------------------- | ---------------------- | ------------------------------------------------------------------------ | ---------------- |
| Kunjungan didaftarkan             | `KunjunganDidaftarkan` | POST Encounter                                                           | `arrived`        |
| Dokter memulai pemeriksaan        | `PemeriksaanDimulai`   | PUT Encounter                                                            | `in-progress`    |
| Pemeriksaan selesai + cara keluar | `PemeriksaanSelesai`   | PUT Encounter (cara keluar)                                              | `finished`       |

- `statusHistory` dibentuk dari `reg_kunjungan_riwayat_status`; `period.start/end` dari `waktu_mulai/waktu_selesai`; `serviceType` dari `mtr_poli.kode_tipe_pelayanan`; `location` dari `mtr_poli.ihs_location_id`; `class` rawat jalan = AMB (kode resmi: verifikasi).
- `cara keluar` diambil dari `reg_kunjungan.kode_cara_keluar` (diisi dokter saat pemeriksaan difinalkan, D41). Encounter `finished` dikirim saat `PemeriksaanSelesai`, bukan saat kunjungan `selesai`; pembayaran farmasi/lab (D37–D43) tidak memengaruhi kiriman SATUSEHAT.
- Lab mandiri & apotek umum membuat Encounter sendiri; praktisi = dokter lab (`lab_permintaan.dokter_id`) / apoteker (`mtr_nakes`).

## 4. Event & ketergantungan (graf)

```
Encounter(arrived) ─► Encounter(in-progress) ─► Encounter(finished)
     │
     ├─► Observation TTV                              (induk: Encounter)
     ├─► ServiceRequest ─► Specimen ─► Observation(hasil)   (induk: Encounter)
     └─► Composition  ◄── Encounter + Observation + ServiceRequest + Specimen (dikirim TERAKHIR)
```

Event: `KunjunganDidaftarkan`, `TtvDisimpan`, `PemeriksaanDimulai`, `PemeriksaanSelesai`, `LabDiminta`, `SpesimenDiambil`, `LabDikunci`. `ResepDiterbitkan` dan `ResepDiserahkan` tetap event domain, tetapi tidak punya listener SATUSEHAT selama Medication* di luar cakupan.

## 5. `sts_sinkronisasi` (satu baris = satu resource yang dikirim)

Kolom kunci: `jenis_resource`, `aksi` (POST/PUT), `referensi_tabel`+`referensi_id`+`sub_kunci` (mis. satu `reg_ttv` → beberapa Observation: sub_kunci `tekanan_darah`, `nadi`, `frekuensi_napas`, `suhu`, `berat_badan`, `tinggi_badan`, `saturasi_oksigen`, `gula_darah`, `skala_nyeri`), `status`, `bergantung_pada_id`, `ihs_id`, `jumlah_percobaan`, `payload`, `respons`, `pesan_galat`. Unik per `(jenis_resource, aksi, referensi_tabel, referensi_id, sub_kunci)`.
`ihs_id` resource baru (ServiceRequest, Specimen, Observation, Composition) disimpan **di tabel ini**, bukan kolom baru di tabel domain. Encounter tetap juga di `reg_kunjungan.ihs_encounter_id`. `erm_diagnosis.ihs_condition_id` tidak diisi selama Condition di luar cakupan.

## 6. Pemetaan data lokal → resource

| Resource                       | Sumber lokal                                       | Terminologi                                       |
| ------------------------------ | -------------------------------------------------- | ------------------------------------------------- |
| Patient (prasyarat)            | `mtr_pasien.ihs_number` (cari via NIK)             | —                                                 |
| Practitioner                   | `mtr_dokter`, `mtr_nakes` `ihs_practitioner_id`    | —                                                 |
| Location                       | `mtr_poli`, `mtr_lokasi` `ihs_location_id`         | —                                                 |
| Encounter                      | `reg_kunjungan` + riwayat status                   | AMB, serviceType, cara keluar                     |
| Observation TTV                | `reg_ttv` (TD, nadi, frekuensi napas, suhu, BB, TB, SpO2, gula darah, skala nyeri) | LOINC (konstanta di `config/satusehat.php`), UCUM |
| ServiceRequest                 | `lab_permintaan_detail` — **satu per kode**        | LOINC `mtr_pemeriksaan_lab.kode_loinc`            |
| Specimen                       | `lab_spesimen`                                     | SNOMED CT `kode_spesimen`                         |
| Observation hasil              | `lab_hasil` ← `mtr_parameter_lab`                  | LOINC parameter, UCUM, interpretasi               |
| Composition                    | Encounter + Observation + ServiceRequest + Specimen + SOAP | LOINC (`Composition.type`, section)     |

Tidak dipetakan selama D33 berlaku: Condition (`erm_diagnosis`), Medication/MedicationRequest (`erm_resep_item`), MedicationDispense (`apt_pengeluaran_resep`). Diagnosis dan obat tetap tersimpan lokal dan masuk narasi SOAP/Composition.

## 7. Composition (resume medis)

- Dikirim **terakhir** per kunjungan, **satu payload** yang memuat section berisi referensi ke resource yang sudah punya ID (Encounter, Observation, ServiceRequest, Specimen) dan/atau narasi.
- Diagnosis (ICD-10) dan obat (e-resep) masuk sebagai **narasi** section, bukan referensi Condition/MedicationRequest, selama D33 berlaku.
- `Composition.type` di halaman resmi saat ini memakai LOINC `88645-7`; PDF playbook lama memakai `18842-5` → **ikuti halaman resmi terbaru**, verifikasi saat implementasi.
- Section wajib dan format narasi: baca halaman Composition + Bab "Pengiriman Data Resume Medis" pada modul Rawat Jalan; catat di `satusehat-payload-composition.md`.
- Hanya dibuat bila ada isi; narasi bisa diambil dari SOAP (S, O, A, P) sehingga SOAP berbasis teks tetap berguna.
- **[SEKUNDER]** dashboard memantau rasio Composition terhadap Encounter (idealnya mendekati 1:1) — pantau di monitor kita.

## 8. Job, retry, error

- Antrean `satusehat`; `tries = 3`, `backoff = [60, 300, 900]` detik; `ShouldBeUnique` per baris `sts_sinkronisasi`; sebelum dikirim, job memeriksa induk (`bergantung_pada_id`) sudah `berhasil` — jika belum, `release()` dengan jeda (tidak menghitung sebagai percobaan gagal).
- Gagal 3x → `failed_jobs` + status `gagal` + pesan galat + notifikasi admin; **Manual Re-sync** mengembalikan ke `menunggu` (satuan/massal) tanpa menggandakan resource yang sudah berhasil.
- **[USULAN]** 5xx/timeout/401 → retry; 4xx validasi → langsung `gagal` (simpan pesan agar bisa diperbaiki lalu Re-sync).
- **Prasyarat tidak lengkap** (pemeriksaan lab tanpa LOINC/spesimen, nakes tanpa IHS) → status `gagal` dengan pesan jelas, bukan error umum. Laporan "master belum terpetakan" (menu `satusehat.pemetaan`) membantu staf melengkapi.
- Payload/respons = data pribadi: tidak di-log, akses terbatas.

## 9. Monitor

Menu `satusehat.sinkronisasi`: ringkasan per jenis resource (menunggu/berhasil/gagal), tabel gagal + pesan, filter, Re-sync, rasio Composition:Encounter. Menu `satusehat.pemetaan`: master belum terpetakan (pemeriksaan/parameter lab→LOINC & spesimen, nakes→IHS). Pemetaan obat→KFA tidak lagi menjadi prasyarat selama D33.

## 10. Pengujian

`SatuSehatClient` (interface) + `FakeSatuSehatClient`. Tes: tiap siklus Encounter; urutan & ketergantungan; retry/gagal permanen; satu ServiceRequest per kode; banyak Observation per TTV (sub_kunci); idempotensi PUT; prasyarat belum terpetakan; Composition hanya setelah Encounter finished; tidak ada baris sinkronisasi untuk Condition/Medication* saat resep diterbitkan atau diserahkan; simpan data medis tetap sukses saat SATUSEHAT mati. Uji end-to-end hanya di sandbox.
