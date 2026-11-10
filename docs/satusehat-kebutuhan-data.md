# Analisis Kebutuhan Data SATUSEHAT — SIM Klinik Avicena

Simpan sebagai `docs/satusehat-kebutuhan-data.md`. Dokumen ini menjawab: **untuk sebuah KLINIK (bukan RS) yang menjalankan ERM, e-resep, dan laboratorium, resource FHIR apa yang perlu dikirim ke SATUSEHAT, dan data apa di sistem kita yang belum cukup untuk itu.**

> **REVISI 29 Okt 2026 (D33): cakupan dipangkas.** Yang dikirim hanya **Encounter, Observation (TTV + hasil lab), Composition, ServiceRequest, dan Specimen**. Condition, Medication, MedicationRequest, MedicationDispense, dan DiagnosticReport **tidak dikirim**. Analisis Condition/Medication/DiagnosticReport di bawah adalah riwayat perencanaan, bukan target implementasi. Prasyarat Organization, Location, Practitioner, dan Patient tetap berlaku.

## 0. Cara membaca & tingkat keyakinan
Setiap klaim diberi label:
- **[RESMI]** — dari portal dokumentasi SATUSEHAT (`satusehat.kemkes.go.id/platform/docs`): playbook *Resume Medis – Rawat Jalan*, *Pelayanan Kefarmasian*, halaman resource MedicationRequest dan Specimen, halaman terminologi.
- **[SEKUNDER]** — blog/vendor RME. Berguna sebagai petunjuk, **bukan** dasar hukum.
- **[INFERENSI]** — kesimpulan saya dari data di atas; perlu Anda konfirmasi.

> **Batasan jujur:** dokumentasi resmi yang saya baca menjelaskan *bagaimana* mengirim tiap resource, tetapi **tidak menyatakan subset minimum yang wajib untuk klinik**. Kewajiban/tenggat/kriteria penilaian per jenis fasyankes harus dikonfirmasi ke Kemenkes/Dinkes/dashboard SATUSEHAT. Jangan menjadikan dokumen ini dasar kepatuhan hukum.

## 1. Apa yang sudah jelas
1. **[RESMI]** Modul *Pelayanan Kefarmasian* secara eksplisit berlaku untuk **RS / Klinik / DPM / Puskesmas** (alur: Encounter → peresepan MedicationRequest → pengeluaran MedicationDispense).
2. **[RESMI]** Playbook *Resume Medis – Rawat Jalan* (versi 6.3) mencakup 28 tahap; yang relevan dengan ERM + e-resep + lab klinik Anda: Patient, Encounter, Condition, Observation (pemeriksaan fisik/TTV), lab (ServiceRequest, Specimen, Observation, DiagnosticReport), obat (Medication, MedicationRequest, MedicationDispense).
3. **[RESMI]** Prasyarat sebelum mengirim data klinis: autentikasi, registrasi **Organization**, **Location**, dan menyimpan nomor IHS **Practitioner**; pasien perlu `patient-ihs-number` dari MPI.
4. **[SEKUNDER]** Kriteria penilaian awal faskes berfokus pada **Encounter + Condition** (surat 2023: minimal 50% data selama 4 minggu). Untuk **rumah sakit**, surat Maret 2026 disebut menambah MedicationRequest, MedicationDispense, Specimen, ImagingStudy. **Untuk klinik, saya tidak menemukan kriteria resmi yang serupa** → tanyakan ke Dinkes.
5. **[KONFIRMASI PEMILIK, 20 Okt 2026]** Akun SATUSEHAT klinik menampilkan resource: **Observation, Composition, ServiceRequest, Specimen, Medication, MedicationRequest, MedicationDispense** (selain Encounter & Condition). Ini bukti paling langsung tentang apa yang diharapkan akun klinik ini. **DiagnosticReport dan Procedure tidak tampil** di daftar itu.
6. **[RESMI]** Klinik tanpa radiologi tidak memerlukan ImagingStudy/DICOM. Rawat inap-spesifik (MedicationAdministration, NutritionOrder, EpisodeOfCare untuk program) tidak relevan.

## 2. Resource yang perlu dikirim (klasifikasi untuk klinik Anda)

> Status target per 29 Okt 2026: hanya **Encounter, Observation, ServiceRequest, Specimen, Composition**. Baris **Condition**, **Medication/MedicationRequest/MedicationDispense**, dan **DiagnosticReport** di tabel ini disimpan sebagai analisis awal dan **tidak diimplementasikan** (D33).

| Lapis | Resource | Sumber data kita | Keyakinan | Catatan |
|---|---|---|---|---|
| **Prasyarat (sekali/master)** | Organization, Location, Practitioner, Patient | klinik, `mtr_poli`, `mtr_dokter`, `mtr_pasien` | RESMI | Kolom IHS sudah ada untuk poli/dokter/pasien. **Belum ada untuk apoteker, perawat, petugas lab** (lihat §4.1) |
| **A. Inti** | **Encounter** | `reg_kunjungan`, `reg_antrean` | RESMI | Berupa **siklus** beberapa kali kirim/ubah, bukan sekali (§3) |
| **A. Inti** | **Condition** (diagnosis ICD-10) | `erm_diagnosis` | RESMI | Sudah sesuai rancangan |
| **B. Layanan Anda: obat** | **MedicationRequest** | `erm_resep`, `erm_resep_item` | RESMI | Butuh data dosis terstruktur (§4.2) |
| **B. Layanan Anda: obat** | **MedicationDispense** | `apt_pengeluaran_resep`, resep diserahkan | RESMI | Kode produk obat **aktual** (93xxxxxx) untuk dispense |
| **B. Layanan Anda: obat** | Medication (data obat) | `mtr_obat`, bahan racikan | RESMI | Sejak v6.2 dikirim **menyatu** di dalam request/dispense, bukan POST terpisah (verifikasi) |
| **C. Layanan Anda: lab** | **ServiceRequest** | `lab_permintaan_detail` | RESMI | **Satu ServiceRequest per satu kode/parameter permintaan** |
| **C. Layanan Anda: lab** | **Specimen** | *(belum ada datanya)* | RESMI | `Specimen.type` wajib dan memakai SNOMED CT |
| **C. Layanan Anda: lab** | **Observation** (hasil) | `lab_hasil` | RESMI | Kode LOINC + satuan baku |
| **C. Layanan Anda: lab** | **DiagnosticReport** | `lab_permintaan` | RESMI | Merangkum Observation + Specimen |
| **D. Resume medis** | **Composition** | referensi ke semua ID resource + narasi SOAP | RESMI + KONFIRMASI AKUN | Dikirim **terakhir**; `Composition.type` ikuti halaman resmi terbaru (LOINC) |
| **Direkomendasikan** | **Observation TTV** (TD, nadi, suhu, BB) | `reg_ttv` | RESMI (pemeriksaan fisik/BB-TB) + SEKUNDER | Kita memang mengumpulkan TTV; ikut dikirim agar data klinis utuh |
| **Opsional / tunda** | Procedure (tindakan), AllergyIntolerance, ClinicalImpression, CarePlan, riwayat penyakit | `erm_tindakan` dll. | RESMI (ada di playbook) | Tidak tampil di akun; tunda sampai ada keputusan |
| **Menunggu verifikasi** | DiagnosticReport (lab) | `lab_permintaan` | RESMI (playbook lab memuatnya) | Tidak tampil di akun; baca halaman resminya & konfirmasi apakah perlu |
| **Tidak relevan** | ImagingStudy, MedicationAdministration, NutritionOrder, EpisodeOfCare (program), Immunization, dll. | — | INFERENSI | Tidak ada layanan terkait di PRD |

### Urutan implementasi yang disarankan [INFERENSI]
1. **Tahap A**: prasyarat + Encounter (siklus) + Condition — paling pasti dinilai.
2. **Tahap B**: MedicationRequest → MedicationDispense (data resep sudah dekat; butuh pemetaan KFA + dosis terstruktur).
3. **Tahap C**: lab (4 resource; butuh LOINC, SNOMED spesimen, dan pencatatan spesimen yang belum ada) + Observation TTV.
4. **Tahap D**: **Composition** (resume medis) — setelah A, B, C karena mereferensikan ID resource lain.
5. **Tahap E**: opsional (Procedure, alergi, dst.) sesuai keputusan baru; DiagnosticReport setelah diverifikasi.

## 3. Koreksi penting pada rancangan `satusehat.md`
Rancangan awal mengirim **Encounter + Condition sekali** setelah "Simpan Data Medis". Dokumentasi resmi menunjukkan:
- **[RESMI]** Encounter dibuat saat **pendaftaran kunjungan** dengan status `arrived`, diubah ke `in-progress` saat pasien dilayani, lalu di-**PUT** menjadi `finished` beserta diagnosis dan kondisi keluar. Pembaruan memakai `Encounter.id` hasil balikan.
- **[RESMI]** Elemen berikut ditandai **wajib** pada pemetaan Encounter: identifier, status, **statusHistory**, class (AMB untuk rawat jalan), **classHistory**, subject, participant (type + individual), period, diagnosis.condition, kondisi/cara keluar, location, serviceProvider.
- **[RESMI]** Waktu dikirim dalam **UTC+00** (WIB dikurangi 7 jam); tanggal tidak boleh sebelum 3 Juni 2014.

Dampaknya pada desain:
1. **Event berganda**, bukan satu: `KunjunganDidaftarkan` (arrived), `PemeriksaanDimulai` (in-progress), `PemeriksaanSelesai` (finished + Condition), `ResepDiterbitkan`, `ResepDiserahkan`, `LabDiminta`, `SpesimenDiambil`, `LabDikunci`, `TtvDisimpan`.
2. **Urutan & ketergantungan**: Encounter dulu; ServiceRequest sebelum Specimen/Observation; Observation sebelum DiagnosticReport; MedicationRequest sebelum MedicationDispense. `sts_sinkronisasi` perlu kolom ketergantungan agar job menunggu induknya.
3. **Pembaruan (PUT)** harus idempotent dan menyimpan `ihs_id` tiap resource.
4. **Riwayat status kunjungan** harus tercatat dengan waktu (untuk `statusHistory`), sedangkan sistem kita saat ini hanya menyimpan status terakhir.

## 4. Celah data (gap) terhadap skema saat ini

### 4.1 Master & identitas
| Kebutuhan | Kondisi sekarang | Usulan |
|---|---|---|
| Kode obat KFA (aktual 93xxxxxx; kemungkinan juga kode virtual untuk request — **verifikasi**) | `mtr_obat.kode_kfa` ada, nullable, tanpa aturan | tambah `nama_kfa`; siapkan kolom kode aktual & virtual; status "terpetakan" |
| Bentuk sediaan & satuan kekuatan/dosis (OrderableDrugForm/UCUM) | tidak ada | kolom kode bentuk sediaan + satuan |
| LOINC pemeriksaan & parameter lab | tidak ada | `kode_loinc`, `nama_loinc` di `mtr_pemeriksaan_lab` dan `mtr_parameter_lab` |
| Satuan hasil lab baku (UCUM), tipe nilai (numerik/kualitatif) | satuan teks bebas | `satuan_ucum`, `jenis_nilai`; hasil kualitatif butuh kode terkode (kemungkinan SNOMED) |
| Jenis spesimen (SNOMED CT) | tidak ada | `kode_spesimen`, `nama_spesimen` di `mtr_pemeriksaan_lab` |
| IHS Practitioner untuk **apoteker, perawat, petugas lab** (performer/recorder) | hanya `mtr_dokter` | NIK terenkripsi + `ihs_practitioner_id` pada tenaga kesehatan non-dokter (kolom di `sys_pengguna` atau tabel `mtr_nakes`) |
| Location untuk apotek/farmasi (lokasi dispense) | hanya `mtr_poli` (INT/GIG/SRF/LAB) | tentukan unit lokasi apotek |
| Kode Tipe Pelayanan (`Encounter.serviceType`) per poli | tidak ada | pemetaan di `mtr_poli` (kode dari lampiran terminologi) |

### 4.2 Transaksi
| Kebutuhan | Kondisi sekarang | Usulan |
|---|---|---|
| Waktu datang / mulai / selesai kunjungan + riwayat status | hanya `status` terakhir | `reg_kunjungan_riwayat_status` (status, waktu, oleh) + `waktu_mulai`, `waktu_selesai` |
| Cara keluar / kondisi saat keluar | tidak ada | kolom terkode pada kunjungan (dipulangkan, dirujuk, dst.) |
| Dosis terstruktur resep: dosis, satuan dosis, frekuensi/interval, **rute**, durasi, aturan tambahan | hanya `aturan_pakai` teks + `jumlah` | kolom terstruktur di `erm_resep_item` (teks bebas tetap disimpan) |
| Racikan sebagai satu Medication dengan bahan-bahan (ingredient) | bahan ada di `erm_resep_racikan_bahan` | pastikan tiap bahan punya KFA; cara pemodelan racikan di FHIR **verifikasi** |
| **Pencatatan spesimen**: jenis, waktu pengambilan, petugas | tidak ada | `lab_spesimen` baru (atau kolom pada `lab_permintaan_detail`) |
| Nilai kualitatif hasil + interpretasi baku | `penanda` normal/rendah/tinggi | pemetaan ke kode interpretasi baku (konstanta di `config/satusehat.php`) |
| `ihs_id` & ketergantungan per resource (ServiceRequest, Specimen, Observation, DiagnosticReport, MedicationRequest, MedicationDispense, Observation TTV) | hanya Encounter & Condition punya kolom | **pusatkan di `sts_sinkronisasi`**: tambah `sub_kunci` (untuk banyak Observation per TTV/hasil) dan `bergantung_pada_id` |

Catatan: Observation TTV dan hasil lab **juga memakai LOINC**; untuk TTV cukup konstanta di config (empat jenis ukuran), bukan kolom master.

## 5. Daftar verifikasi dari dokumentasi resmi (WAJIB sebelum coding)
Baca halaman resmi tiap resource dan catat elemen bertanda `*` (wajib). Prioritas:
1. **Encounter**: nilai `serviceType`, `dischargeDisposition`, isi `statusHistory`/`classHistory`, kapan `PUT`.
2. **MedicationRequest & MedicationDispense & Medication**: elemen wajib; pemakaian kode KFA virtual vs aktual; cara kirim obat racikan; terminologi rute pemberian dan satuan.
3. **ServiceRequest, Specimen, Observation, DiagnosticReport (lab)**: elemen wajib, kode kategori lab, aturan 1 ServiceRequest per kode, hubungan antar-resource, kode interpretasi, nilai kualitatif.
4. **Observation TTV**: kode LOINC & satuan untuk tekanan darah, nadi, suhu, berat badan.
5. Lampiran terminologi: *Resume Medis – Rawat Jalan*, *Pelayanan Kefarmasian*, *Rujukan Spesimen* (kode spesimen SNOMED).
6. **Regulasi**: konfirmasi ke Dinkes/Kemenkes resource apa yang dinilai/wajib untuk **klinik** Anda dan tenggatnya.

## 6. Keputusan pemilik proyek (20 Okt 2026)
| # | Pertanyaan | Keputusan |
|---|---|---|
| Q15 | Resource yang dikirim | Tahap A, B, C, lalu D (Composition); disesuaikan dengan daftar resource di akun SATUSEHAT klinik |
| Q16 | Observation TTV ikut dikirim? | **Ya** |
| Q17 | Procedure/alergi/resume medis? | **Direvisi**: Composition (resume medis) **masuk** karena tampil di akun; Procedure, alergi, ClinicalImpression tetap **ditunda** |
| Q18 | Pencatatan spesimen | Tabel baru `lab_spesimen` |
| Q19 | Nakes non-dokter | Tabel `mtr_nakes` (akun login opsional, terpisah dari data nakes) |
| Q20 | Lokasi apotek | Tabel `mtr_lokasi` (seed kode `APT`) |
| Q21 | DiagnosticReport | **Terbuka**: tidak tampil di akun; verifikasi halaman resminya, putuskan setelah Tahap C |
| Q22 | Section wajib Composition & kode terminologinya | **Terbuka**: baca halaman Composition & Bab Resume Medis (Rawat Jalan) saat Tahap D |

## 7. Dampak pada dokumen lain (sudah disiapkan di paket pemetaan: lihat `pembaruan-dokumen.md`)
`satusehat.md` (siklus Encounter, event, ketergantungan), `database.md` (migration baru), `roadmap.md` (Fase 7 dipecah 7A–7D + Fase 7.0 pemetaan terminologi), `decisions.md` (D14 diperluas; D15–D20; Q15–Q22), `prompts.md` (prompt 3.1 dan 7.x), `business-rules.md` (§5 e-resep: dosis terstruktur; §7 lab: spesimen).

## 8. Sumber
- Portal dokumentasi SATUSEHAT: Panduan Interoperabilitas → *Resume Medis – Rawat Jalan*, *Pelayanan Kefarmasian*; FHIR → *MedicationRequest*, *MedicationDispense*, *Specimen*, *Procedure*; Terminologi → *SNOMED-CT*, *LOINC*, *Standar Terminologi*; Master Data → *KFA*.
- Sekunder (indikatif): ulasan 19 resource SATUSEHAT (assist.id), panduan integrasi RS (medminutes.io), integrasi klinik (myklinik.id).
