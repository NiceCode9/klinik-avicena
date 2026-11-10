# Prompt Fase 7 (pengganti)

Simpan sebagai `docs/prompts-fase7.md`. Semua prompt memakai protokol **PEMBUKA** dari `prompts.md` (tempel sekali di awal sesi). Bagian "Fase 7" di `prompts.md` digantikan oleh dokumen ini.

> Perubahan prompt Fase 2–5 sudah dipindahkan ke `prompts.md` (2.3, 3.1, 3.6, 3.7, 4.1–4.3, 5.4). Dokumen ini hanya untuk Fase 7.

> **REVISI 29 Okt 2026 (D33):** cakupan SATUSEHAT hanya **Encounter, Observation (TTV + hasil lab), Composition, ServiceRequest, Specimen**. **Jangan kerjakan** 7A.5 (Condition), 7B (Medication/MedicationRequest/MedicationDispense), dan 7C.4 (DiagnosticReport). Prompt bagian itu disimpan sebagai arsip dan tidak boleh dipakai.

**Aturan tambahan untuk SEMUA prompt SATUSEHAT** (sudah dimasukkan ke tiap prompt di bawah):
- Sebelum menulis payload suatu resource, baca halaman resmi resource itu di `satusehat.kemkes.go.id/platform/docs` (dan lampiran terminologinya), catat elemen wajib (tanda `*`), kode terminologi, dan sumbernya di `docs/satusehat-payload-<resource>.md`. Jika kamu tidak bisa mengakses dokumentasi, MINTA aku menempelkan halamannya. **Jangan menebak. Jangan memakai PDF playbook lama.**
- Hal yang belum bisa diverifikasi → komentar `TODO(verifikasi-resmi)` + laporkan di akhir.

---

# Fase 7 — Integrasi SATUSEHAT (pengganti)
Dokumen utama: `satusehat.md`, `satusehat-kebutuhan-data.md`, `decisions.md` (D14–D33, Q21–Q23).

## 7.0 Pemetaan terminologi & master pendukung

### 7.0a Pasang paket pemetaan + relasi model
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: pasang perubahan skema pemetaan SATUSEHAT. Kondisi: file migration
2026_10_21_000001_tambah_pemetaan_dan_kebutuhan_data_satusehat.php, model baru (Master\Nakes,
Master\Lokasi, Registrasi\KunjunganRiwayatStatus, Lab\Spesimen) dan PemetaanSatuSehatSeeder SUDAH disalin.
Lakukan: (1) php artisan migrate lalu seed PemetaanSatuSehatSeeder (jika belum); (2) tambahkan relasi/cast pada MODEL YANG SUDAH ADA
(jangan menimpa kode kustom): Kunjungan::riwayatStatus(); cast datetime waktu_datang/waktu_mulai/waktu_selesai;
Lab\Permintaan::spesimen() (hasMany); Lab\PermintaanDetail::spesimen() (belongsTo); Erm\ResepItem cast
dosis decimal:3; SatuSehat\Sinkronisasi::induk() dan dependen(); App\Models\User::nakes() (hasOne);
Master\Dokter/Nakes tidak berubah; (3) jalankan php artisan test; gunakan migrate:fresh --seed HANYA jika
data lokal boleh dihapus (minta persetujuan).
Jangan mengubah migration yang sudah ada. Laporkan model yang kamu ubah.
```

### 7.0b Master: kolom pemetaan di form + CRUD Nakes & Lokasi
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: (1) tambahkan kolom pemetaan ke form & tampilan master yang sudah ada: Obat (kode_kfa = kode produk AKTUAL,
kode_kfa_virtual, nama_kfa, kode_bentuk_sediaan, nama_bentuk_sediaan), Pemeriksaan Lab (kode_loinc, nama_loinc,
kode_spesimen, nama_spesimen), Parameter Lab (kode_loinc, nama_loinc, satuan_ucum, jenis_nilai), Poli
(kode_tipe_pelayanan, nama_tipe_pelayanan); semua opsional di form. (2) CRUD Master Nakes (menu master.nakes; NIK
terenkripsi + nik_hash lewat NikHasher; pengguna_id opsional) dan Master Lokasi (menu master.lokasi).
Ikuti pola CRUD master yang sudah ada, izin, dan tes. Kode terminologi diisi manual dulu (pencarian otomatis
ke layanan resmi dibahas terpisah). Tambahkan menu ke config/menu.php
(docs/pembaruan-dokumen.md §6; prosedur umum di docs/permissions.md bagian
"Menambah menu / fitur baru") lalu menu:sinkron.
Tes: simpan kolom pemetaan, NIK nakes terenkripsi, izin admin saja untuk nakes/lokasi.
```

### 7.0c Laporan "master belum terpetakan"
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: halaman Pemetaan Terminologi (menu satusehat.pemetaan, admin) yang menampilkan master yang BELUM lengkap untuk SATUSEHAT:
pemeriksaan lab tanpa kode_loinc/kode_spesimen, parameter lab tanpa kode_loinc/satuan_ucum,
poli tanpa kode_tipe_pelayanan/ihs_location_id, dokter/nakes tanpa ihs_practitioner_id, lokasi tanpa ihs_location_id.
KFA tidak dicek sebagai prasyarat (D33: Medication* di luar cakupan).
Ringkasan jumlah per kategori + daftar dengan tautan ke form edit. Satu service PemetaanService::belumTerpetakan()
yang dipakai juga oleh job (7A) untuk memvalidasi prasyarat. Query agregat di database, paginate.
Tes: tiap kategori terdeteksi benar, item lengkap tidak muncul, izin admin.
```

### 7.0d Referensi terminologi + picker
> Dikerjakan setelah laporan 7.0c terbukti dipakai. Jangan menggabungkan 7.0d.1–7.0d.3 dalam satu sesi. KFA bersifat opsional selama D33; jangan menjadikannya prasyarat.

#### 7.0d.1 Tabel referensi + perintah impor
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: buat tabel/model referensi terminologi dan perintah impor CSV untuk LOINC, SNOMED CT, UCUM, dan KFA.
Dokumen: satusehat-fondasi.md §5 (tiga tingkat pemetaan), decisions.md D23–D27, database.md, AGENTS.md (prefix mtr_ = Master).
Cakupan:
- Migration BARU mtr_terminologi: id, enum sistem (kfa/loinc/snomed/ucum), kode (50), display (500),
  metadata JSON nullable, aktif boolean default true, timestamps; unik (sistem,kode); index (sistem).
  Jangan mengubah migration lama.
- Model App\Models\Master\Terminologi (extends ModelDasar), konstanta SISTEM + label, cast metadata array/aktif boolean,
  scope aktif.
- Command satusehat:impor-terminologi {sistem} {berkas} {--sumber=} {--versi=}:
  - Validasi sistem, file terbaca, header CSV kode,display,metadata (metadata opsional, JSON valid; trim BOM).
  - Upsert per (sistem,kode) per chunk; metadata digabung dengan sumber/versi/tanggal_unduh.
  - Baris invalid dilaporkan (nomor baris + alasan) dan dilewati; impor ulang idempoten
    (tidak menggandakan, display/metadata diperbarui).
  - JANGAN commit dataset penuh. File dibaca dari path lokal hasil unduhan resmi.
  - KFA: pakai CSV dulu. Sinkron API resmi KFA hanya kalau dokumentasi endpoint resmi tersedia; kalau belum,
    tulis TODO(verifikasi-resmi) dan jangan menebak URL/token.
Tes: impor valid, idempoten, update display/metadata, sistem invalid, file hilang, baris rusak, tidak ada duplikat.
Selesai bila: php artisan test lulus; model + command terdaftar; tidak ada dataset besar di repo.
```

#### 7.0d.2 Picker + validasi kode
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: tambahkan pencarian referensi dan validasi kode pada form master yang sudah punya kolom pemetaan.
Dokumen: satusehat-fondasi.md §5.2 (manusia memilih; tidak ada auto-map), permissions.md, conventions.md.
Backend:
- Route GET satusehat/terminologi/cari name satusehat-terminologi.cari, middleware izin:satusehat.pemetaan,lihat;
  controller SatuSehat\TerminologiController@cari (tipis) memakai TerminologiService::cari(); kembalikan JSON
  [{kode,display,metadata}] maks 20, filter sistem + aktif, q minimal 2 karakter, escape wildcard LIKE.
- Rule App\Rules\KodeTerminologi(sistem): kode harus ada di mtr_terminologi aktif; pesan menyebut sistem dan
  perintah impor. Nullable tetap boleh (artinya belum terpetakan).
- Pasang rule: SimpanObatRequest (kode_kfa, kode_kfa_virtual = kfa); SimpanPemeriksaanLabRequest
  (kode_loinc + parameter.*.kode_loinc = loinc; kode_spesimen = snomed; parameter.*.satuan_ucum = ucum);
  SimpanSatuanRequest (kode_ucum = ucum). kode_tipe_pelayanan poli TETAP manual (di luar empat sistem).
- Jangan mengisi kode_kfa dari kode_kfa_virtual atau sebaliknya; dua field terpisah tanpa fallback.
UI:
- Partial resources/views/satusehat/terminologi/_tombol.blade.php: tombol Cari di samping input kode yang sudah ada
  (name, old(), dan @error tidak berubah). Hanya tampil untuk @izin('satusehat.pemetaan','lihat').
  Bungkus input + tombol dalam .input-group; JS mengambil input dari grup itu.
- Partial _modal.blade.php: satu modal Bootstrap per form, di-include sekali di form (bukan di _baris_parameter).
  Isi: input pencarian, daftar hasil, loading/empty state, fokus otomatis. Pilih hasil -> isi input kode + target display:
  kode_loinc -> nama_loinc; kode_spesimen -> nama_spesimen; kode_kfa (aktual) -> nama_kfa + kode_bentuk_sediaan/
  nama_bentuk_sediaan dari metadata bila ada; kode_kfa_virtual dan UCUM tidak mengisi display.
  Target dicari lewat sufiks name di tr/form terdekat, bukan name ber-index global.
- JS vanilla tanpa package baru; event delegation agar baris parameter dinamis ikut; debounce 300ms;
  render hasil dengan textContent (bukan innerHTML data server); Enter pada hasil dan Esc untuk menutup.
- Input manual tetap boleh; validasi saat simpan yang menentukan. Tidak ada auto-map atau pemilihan otomatis kode pertama.
- Jika referensi suatu sistem kosong, form tetap terbuka; simpan kode ditolak dengan pesan "impor terminologi dulu"
  (bukan dianggap valid). Catat di laporan akhir bahwa impor referensi harus dijalankan sebelum validasi dipakai di data lama.
Tes: endpoint cari (kode/display, sistem, aktif, limit, izin), rule tiap field, kode tak dikenal/nonaktif ditolak,
kode valid diterima, nullable boleh, render tombol/modal, dan tidak ada auto-map.
Perbarui tes master yang memakai kode contoh agar mengisi mtr_terminologi lebih dulu (helper/fixture tes).
Selesai bila: php artisan test lulus; grep tidak menemukan penulisan kode pemetaan tanpa validasi (kecuali kode_tipe_pelayanan).
```

#### 7.0d.3 Sinkron KFA via API resmi (hanya jika dokumentasi tersedia)
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: tambahkan command satusehat:sinkron-kfa untuk mengisi mtr_terminologi sistem kfa dari API resmi KFA.
Syarat: baca dokumentasi resmi API KFA (URL, autentikasi, parameter, format respons) dan catat sumbernya.
Jika dokumentasi tidak bisa diakses, JANGAN implementasi; akhiri dengan TODO(verifikasi-resmi) dan laporkan halaman yang dibutuhkan.
Aturan:
- Kredensial hanya dari .env (SATUSEHAT_KFA_*), tidak ditulis di kode/log.
- Cache + rate limit; upsert per (kfa,kode) dengan metadata sumber/versi; idempoten.
- Jangan memanggil API asli di tes; gunakan Http::fake. Tidak ada endpoint/format dari ingatan.
Tes: sukses, respons kosong, 401/429/5xx, idempoten, metadata tersimpan, tidak ada panggilan jaringan asli.
Selesai bila: tes lulus; command hanya ada jika dokumentasi resmi tersedia.
```

## 7A Fondasi + Tahap A (prasyarat, Encounter)

### 7A.1 Client, konfigurasi, token
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: interface SatuSehatClient, implementasi nyata (HTTP client Laravel), FakeSatuSehatClient (tes),
config/satusehat.php (dari env SATUSEHAT_*; termasuk konstanta terminologi yang akan diisi dari dokumentasi resmi),
dan pengelolaan token di sts_token (terenkripsi; refresh sebelum kedaluwarsa).
Aturan: kredensial hanya dari .env; JANGAN menulis endpoint/profil FHIR dari ingatan — baca dokumentasi resmi
(Autentikasi/Akses Token) dan catat sumbernya; sisanya TODO(verifikasi-resmi). Tes memakai Fake client.
```

### 7A.2 Sinkron IHS master
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: IhsService untuk menyelesaikan dan menyimpan ihs_number pasien (via NIK), ihs_practitioner_id dokter & nakes (via NIK),
dan memverifikasi ihs_location_id poli/lokasi. Baca halaman resmi MPI, Practitioner, Location.
NIK tidak boleh masuk log; kegagalan menghasilkan pesan jelas. Tes dengan Fake client; gunakan PemetaanService untuk daftar yang belum lengkap.
```

### 7A.3 Infrastruktur event → sinkronisasi → job
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: infrastruktur umum Fase 7: (1) service SinkronisasiService::buat(jenisResource, aksi, referensi, subKunci, induk)
yang membuat/mengambil baris sts_sinkronisasi (unik per jenis+aksi+referensi+subKunci); (2) SatuSehatSyncJob generik
(queue 'satusehat', tries 3, backoff [60,300,900], ShouldBeUnique per baris) yang: memeriksa induk (bergantung_pada_id)
sudah 'berhasil' — jika belum release() tanpa menghitung percobaan; memanggil PayloadBuilder sesuai jenis_resource;
mengirim lewat SatuSehatClient; menyimpan ihs_id/respons/status; menandai 'gagal' dengan pesan jelas untuk prasyarat
belum lengkap (pakai PemetaanService); (3) kerangka PayloadBuilder per resource (kosong, diisi di tugas berikutnya).
Dokumen: satusehat.md §2, §5, §8. Tes: urutan & ketergantungan, release saat induk belum berhasil, retry/gagal permanen,
idempotensi, tidak ada panggilan jaringan (Fake client).
```

### 7A.4 Encounter (arrived → in-progress → finished)
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: listener untuk KunjunganDidaftarkan, PemeriksaanDimulai, PemeriksaanSelesai + EncounterPayloadBuilder.
Dokumen: satusehat.md §3. WAJIB baca halaman resmi Encounter dan bagian "Pendaftaran Kunjungan Pasien" & "Pembaharuan Data
Kunjungan" pada modul Resume Medis – Rawat Jalan; catat elemen wajib di docs/satusehat-payload-encounter.md.
Alur: POST (arrived) → simpan ihs_encounter_id; PUT in-progress; PUT finished memuat cara keluar.
Diagnosis TIDAK dikirim sebagai Condition (D33); diagnosis masuk narasi SOAP/Composition, bukan `Encounter.diagnosis`.
statusHistory dari reg_kunjungan_riwayat_status; waktu dikonversi ke UTC+00; serviceType dari mtr_poli.
Lab mandiri/apotek umum punya Encounter sendiri. Tes: tiap tahap, urutan PUT, konversi waktu, prasyarat belum terpetakan → gagal berpesan jelas.
```

### 7A.5 Condition — ARSIP (jangan dikerjakan, D33)
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: ConditionPayloadBuilder + listener PemeriksaanSelesai untuk tiap erm_diagnosis (ICD-10), induk = Encounter.
Baca halaman resmi Condition dan bagian Diagnosis; catat di docs/satusehat-payload-condition.md.
Simpan ihs_condition_id di erm_diagnosis dan sts_sinkronisasi. Tes: primer/sekunder, banyak diagnosis (sub_kunci), urutan sebelum PUT finished.
```

## 7B Obat — ARSIP (jangan dikerjakan, D33)

### 7B.1 MedicationRequest
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: MedicationRequestPayloadBuilder + listener ResepDiterbitkan (induk = Encounter). Satu MedicationRequest per item resep.
WAJIB baca halaman resmi MedicationRequest, Medication, dan modul Pelayanan Kefarmasian + lampiran terminologinya;
catat elemen wajib dan cara pengiriman Medication (menyatu vs terpisah) serta pemodelan RACIKAN di
docs/satusehat-payload-medication.md. Pakai dosis terstruktur erm_resep_item (rute, frekuensi, durasi).
Item obat belum terpetakan (tanpa KFA) → status gagal berpesan jelas. Tes: reguler, racikan, dosis terstruktur, prasyarat.
```

### 7B.2 MedicationDispense
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: MedicationDispensePayloadBuilder + listener ResepDiserahkan (induk = MedicationRequest terkait). Gunakan KFA kode AKTUAL
(kolom kode_kfa) sesuai halaman resmi MedicationDispense/Pelayanan Kefarmasian; performer = apoteker (mtr_nakes + ihs_practitioner_id);
lokasi = mtr_lokasi APT; waktu serah dari erm_resep.diserahkan_pada (UTC+00). Catat di docs/satusehat-payload-medicationdispense.md.
Tes: urutan setelah MedicationRequest, nakes tanpa IHS → gagal berpesan jelas, banyak item.
```

## 7C Laboratorium + TTV

### 7C.1 ServiceRequest & Specimen
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: payload builder + listener untuk LabDiminta (ServiceRequest, SATU per kode pemeriksaan, induk = Encounter) dan SpesimenDiambil
(Specimen, induk = ServiceRequest). Baca halaman resmi ServiceRequest, Specimen, bagian "Pengiriman Data Pemeriksaan Penunjang Laboratorium"
dan lampiran terminologi spesimen; catat di docs/satusehat-payload-lab.md. LOINC dari mtr_pemeriksaan_lab, SNOMED spesimen dari lab_spesimen.
Requester = dokter perujuk (rujukan) atau dokter lab (mandiri). Tes: satu ServiceRequest per kode, ketergantungan, prasyarat belum terpetakan.
```

### 7C.2 Observation hasil lab
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: ObservationLabPayloadBuilder + listener LabDikunci: satu Observation per baris lab_hasil (sub_kunci = id hasil), induk = Specimen/ServiceRequest.
Baca halaman resmi Observation dan bagian hasil laboratorium; catat kode kategori, interpretasi baku, penanganan nilai kualitatif,
satuan UCUM di docs/satusehat-payload-observation.md. Nilai numerik/kualitatif sesuai mtr_parameter_lab.jenis_nilai.
Tes: numerik, kualitatif, interpretasi, parameter tanpa LOINC → gagal berpesan jelas.
```

### 7C.3 Observation TTV
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: ObservationTtvPayloadBuilder + listener TtvDisimpan: satu Observation per ukuran (tekanan darah, nadi, frekuensi napas,
suhu, berat badan, tinggi badan, saturasi oksigen, gula darah, skala nyeri; sub_kunci per ukuran), induk = Encounter.
Kode LOINC dan satuan UCUM sebagai konstanta di config/satusehat.php — ambil dari
halaman resmi (Pemeriksaan Fisik / Pelayanan Kefarmasian untuk BB-TB), catat di docs/satusehat-payload-observation.md.
Tes: sembilan ukuran, nilai kosong dilewati, urutan setelah Encounter.
```

### 7C.4 (Opsional) DiagnosticReport — ARSIP (jangan dikerjakan, D33)
```
Protokol sesi (PEMBUKA) berlaku. JANGAN menulis kode dulu.
Tugas: baca halaman resmi DiagnosticReport dan bagian laporan pemeriksaan laboratorium; ringkas elemen wajib dan hubungannya dengan
Observation/Specimen/ServiceRequest di docs/satusehat-payload-diagnosticreport.md, lalu rekomendasikan apakah perlu dikirim untuk klinik
(Q21: tidak tampil di akun SATUSEHAT klinik). Tunggu keputusanku.
```

## 7D Resume medis

### 7D.1 Composition
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: CompositionPayloadBuilder + listener yang berjalan setelah Encounter 'finished' dan resource lain (Observation,
ServiceRequest, Specimen) berstatus berhasil atau tidak berlaku. Induk logis = semua itu. Diagnosis dan obat masuk
sebagai narasi section, bukan referensi Condition/MedicationRequest (D33).
WAJIB baca halaman resmi Composition dan Bab "Pengiriman Data Resume Medis" (modul Resume Medis – Rawat Jalan) VERSI TERBARU;
catat Composition.type (jangan pakai kode dari PDF lama), section wajib, kode LOINC section, format narasi (SOAP → narasi),
author, custodian di docs/satusehat-payload-composition.md. Satu payload memuat referensi ID dari sts_sinkronisasi.
Jangan membuat Composition tanpa isi. Tes: urutan terakhir, referensi ID benar, tidak dibuat jika Encounter belum finished.
```

## 7E Monitor & uji sandbox

### 7E.1 Monitor, Re-sync, rasio
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: menu satusehat.sinkronisasi: ringkasan per jenis resource & status, tabel gagal dengan pesan galat, filter tanggal/jenis,
Re-sync satuan & massal (kembali ke menunggu + dispatch ulang TANPA menggandakan resource yang sudah berhasil; PUT untuk pembaruan),
badge gagal di dasbor admin, serta rasio Composition:Encounter hari ini.
Payload/respons (data pribadi) tidak ditampilkan penuh kecuali hak khusus dan tidak masuk log. Tes: izin, re-sync idempotent, rasio.
```

### 7E.2 Checklist uji sandbox (tanpa kode)
```
Protokol sesi (PEMBUKA) berlaku. JANGAN menulis kode.
Tugas: buat docs/uji-sandbox-satusehat.md: langkah uji end-to-end di sandbox untuk SEMUA resource Tahap A–D (registrasi organisasi,
kredensial, IHS pasien/dokter/nakes/lokasi uji, siklus Encounter, e-resep→dispense, lab dari permintaan sampai hasil, TTV, Composition),
simulasi gangguan jaringan (retry), simulasi 4xx, skenario master belum terpetakan; kolom hasil yang kuisi manual;
daftar semua TODO(verifikasi-resmi) yang tersisa; dan checklist konfirmasi regulasi klinik ke Dinkes/Kemenkes.
```

### P-AUDIT Fase 7
Gunakan P-AUDIT di `prompts.md` dengan tambahan: periksa bahwa tiap payload builder punya dokumen sumber di `docs/satusehat-payload-*.md`, tidak ada `TODO(verifikasi-resmi)` yang diam-diam menjadi nilai tebakan, tidak ada panggilan SATUSEHAT di request web, dan semua resource terkirim hanya setelah induknya berhasil.
