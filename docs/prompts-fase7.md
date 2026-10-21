# Prompt Fase 7 (pengganti) + Perubahan Prompt Fase 2–5

Simpan sebagai `docs/prompts-fase7.md`. Semua prompt memakai protokol **PEMBUKA** dari `prompts.md` (tempel sekali di awal sesi). Bagian "Fase 7" di `prompts.md` digantikan oleh dokumen ini.

**Aturan tambahan untuk SEMUA prompt SATUSEHAT** (sudah dimasukkan ke tiap prompt di bawah):
- Sebelum menulis payload suatu resource, baca halaman resmi resource itu di `satusehat.kemkes.go.id/platform/docs` (dan lampiran terminologinya), catat elemen wajib (tanda `*`), kode terminologi, dan sumbernya di `docs/satusehat-payload-<resource>.md`. Jika kamu tidak bisa mengakses dokumentasi, MINTA aku menempelkan halamannya. **Jangan menebak. Jangan memakai PDF playbook lama.**
- Hal yang belum bisa diverifikasi → komentar `TODO(verifikasi-resmi)` + laporkan di akhir.

---

# A. Perubahan pada prompt Fase 2–5 (kerjakan SEBELUM/SAAT fase itu)

### Tambahan untuk 2.3 (KunjunganService & StatusKunjunganService)
```
Tambahan pada tugas 2.3 (ikuti docs/business-rules.md §1 dan §11, decisions D14):
- Setiap perpindahan status oleh StatusKunjunganService mencatat baris reg_kunjungan_riwayat_status
  (status, waktu, dibuat_oleh) dalam transaksi yang sama.
- reg_kunjungan.waktu_datang diisi saat kunjungan dibuat; waktu_mulai saat pemeriksaan dimulai;
  waktu_selesai saat pemeriksaan difinalkan.
- Pancarkan event App\Events\KunjunganDidaftarkan dan TtvDisimpan dengan afterCommit.
  JANGAN membuat listener SATUSEHAT atau memanggil API di fase ini.
Tes tambahan: riwayat status terisi untuk tiap transisi; event hanya terpancar setelah commit.
```

### Tambahan untuk 3.1 (PemeriksaanService)
```
Ganti bagian event pada tugas 3.1 dengan (decisions D14):
- mulai(): pancarkan PemeriksaanDimulai (afterCommit), isi waktu_mulai.
- simpanFinal(): wajib mengisi cara keluar (kode_cara_keluar/nama_cara_keluar), isi waktu_selesai,
  pancarkan PemeriksaanSelesai (afterCommit). Tanpa listener SATUSEHAT di fase ini.
- PemeriksaanDimulai dipicu otomatis saat dokter pertama kali membuka form pemeriksaan (Q23).
Tes: event dan waktu terisi benar; final tanpa cara keluar ditolak.
```

### Pengganti 3.6 (e-Resep dengan dosis terstruktur)
```
Gantikan prompt 3.6 dengan tugas ini.
Tugas: ResepService + UI e-Resep (menu erm.resep) dengan DOSIS TERSTRUKTUR.
Dokumen: business-rules §6, database.md (erm_resep_item kolom baru), decisions D14, D15.
Tiap item resep memuat: obat (reguler) atau bahan (racikan), jumlah, dosis + satuan_dosis,
frekuensi + periode + satuan_periode (jam/hari/minggu), rute (kode_rute/nama_rute dari daftar
terminologi yang disetujui), lama_hari, aturan_tambahan, dan aturan_pakai (teks tampilan yang
dibangkitkan otomatis dari kolom terstruktur, boleh disunting). Harga snapshot dari mtr_obat.
Item reguler: obat_id wajib. Racikan: minimal 1 bahan; salin dari template mtr_racikan; dosis berlaku
untuk racikan sebagai satu kesatuan. Obat tanpa kode KFA boleh diresepkan tetapi tampilkan
peringatan "belum terpetakan ke KFA" (tidak memblokir).
Pancarkan ResepDiterbitkan (afterCommit). JANGAN memotong stok.
Langkah 1 (tanpa kode): usulkan daftar nilai rute dan cara pengisian terstruktur di form
(mis. dropdown rute); tunggu persetujuanku. Daftar kode rute resmi diverifikasi di Fase 7.0.
Tes: validasi struktur dosis, pembangkitan aturan_pakai, snapshot harga, peringatan KFA.
```

### Tambahan untuk 4.1–4.3 (Laboratorium)
```
Tambahan pada tugas Fase 4 (business-rules §7, decisions D14, D17):
- Permintaan lab dipecah menjadi SATU BARIS lab_permintaan_detail per kode pemeriksaan.
- Buat SpesimenService + UI (menu lab.spesimen): catat pengambilan spesimen di lab_spesimen
  (jenis dari mtr_pemeriksaan_lab.kode_spesimen, status menunggu_diambil→diambil→diterima|ditolak,
  waktu_pengambilan, diambil_oleh); tautkan lab_permintaan_detail.spesimen_id.
- Hasil hanya boleh diinput jika spesimen berstatus diterima (atau pemeriksaan tanpa spesimen — usulkan aturan).
- Pancarkan LabDiminta (saat permintaan dibuat), SpesimenDiambil, LabDikunci (afterCommit), tanpa listener SATUSEHAT.
Tes: satu detail per kode, alur status spesimen, hasil ditolak tanpa spesimen diterima.
```

### Tambahan untuk 5.4 (Penyerahan resep)
```
Tambahan pada tugas 5.4: setelah penyerahan resep berhasil (transaksi commit), pancarkan ResepDiserahkan
(afterCommit) berisi resep_id dan item yang diserahkan beserta batch (apt_pengeluaran_resep).
Peringatkan apoteker bila ada obat belum terpetakan ke KFA aktual (tidak memblokir penyerahan).
Tes: event terpancar sekali per penyerahan, tidak terpancar saat rollback.
```

---

# B. Fase 7 — Integrasi SATUSEHAT (pengganti)
Dokumen utama: `satusehat.md`, `satusehat-kebutuhan-data.md`, `decisions.md` (D14–D20, Q21–Q23).

## 7.0 Pemetaan terminologi & master pendukung

### 7.0a Pasang paket pemetaan + relasi model
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: pasang perubahan skema pemetaan SATUSEHAT. Kondisi: file migration
2026_10_21_000001_tambah_pemetaan_dan_kebutuhan_data_satusehat.php, model baru (Master\Nakes,
Master\Lokasi, Registrasi\KunjunganRiwayatStatus, Lab\Spesimen) dan PemetaanSatuSehatSeeder SUDAH disalin.
Lakukan: (1) php artisan migrate lalu seed PemetaanSatuSehatSeeder; (2) tambahkan relasi/cast pada MODEL YANG SUDAH ADA
(jangan menimpa kode kustom): Kunjungan::riwayatStatus(); cast datetime waktu_datang/waktu_mulai/waktu_selesai;
Lab\Permintaan::spesimen() (hasMany); Lab\PermintaanDetail::spesimen() (belongsTo); ErmResepItem cast
dosis decimal:3; SatuSehat\Sinkronisasi::induk() dan dependen(); Sys\Pengguna::nakes() (hasOne);
Master\Dokter/Nakes tidak berubah; (3) jalankan php artisan migrate:fresh --seed di DB lokal dan php artisan test.
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
ke layanan resmi dibahas terpisah). Tambahkan menu ke config/menu.php (docs/pembaruan-dokumen.md §6) lalu menu:sinkron.
Tes: simpan kolom pemetaan, NIK nakes terenkripsi, izin admin saja untuk nakes/lokasi.
```

### 7.0c Laporan "master belum terpetakan"
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: halaman Pemetaan Terminologi (menu satusehat.pemetaan, admin) yang menampilkan master yang BELUM lengkap untuk SATUSEHAT:
obat tanpa kode_kfa, pemeriksaan lab tanpa kode_loinc/kode_spesimen, parameter lab tanpa kode_loinc/satuan_ucum,
poli tanpa kode_tipe_pelayanan/ihs_location_id, dokter/nakes tanpa ihs_practitioner_id, lokasi tanpa ihs_location_id.
Ringkasan jumlah per kategori + daftar dengan tautan ke form edit. Satu service PemetaanService::belumTerpetakan()
yang dipakai juga oleh job (7A) untuk memvalidasi prasyarat. Query agregat di database, paginate.
Tes: tiap kategori terdeteksi benar, item lengkap tidak muncul, izin admin.
```

## 7A Fondasi + Tahap A (prasyarat, Encounter, Condition)

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
Alur: POST (arrived) → simpan ihs_encounter_id; PUT in-progress; PUT finished memuat diagnosis & cara keluar.
statusHistory dari reg_kunjungan_riwayat_status; waktu dikonversi ke UTC+00; serviceType dari mtr_poli.
Lab mandiri/apotek umum punya Encounter sendiri. Tes: tiap tahap, urutan PUT, konversi waktu, prasyarat belum terpetakan → gagal berpesan jelas.
```

### 7A.5 Condition
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: ConditionPayloadBuilder + listener PemeriksaanSelesai untuk tiap erm_diagnosis (ICD-10), induk = Encounter.
Baca halaman resmi Condition dan bagian Diagnosis; catat di docs/satusehat-payload-condition.md.
Simpan ihs_condition_id di erm_diagnosis dan sts_sinkronisasi. Tes: primer/sekunder, banyak diagnosis (sub_kunci), urutan sebelum PUT finished.
```

## 7B Obat

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
Tugas: ObservationTtvPayloadBuilder + listener TtvDisimpan: satu Observation per ukuran (tekanan darah, nadi, suhu, berat badan;
sub_kunci per ukuran), induk = Encounter. Kode LOINC dan satuan UCUM sebagai konstanta di config/satusehat.php — ambil dari
halaman resmi (Pemeriksaan Fisik / Pelayanan Kefarmasian untuk BB-TB), catat di docs/satusehat-payload-observation.md.
Tes: empat ukuran, nilai kosong dilewati, urutan setelah Encounter.
```

### 7C.4 (Opsional) DiagnosticReport
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
Tugas: CompositionPayloadBuilder + listener yang berjalan setelah Encounter 'finished' dan resource lain (Condition, Observation,
MedicationRequest, MedicationDispense, ServiceRequest, Specimen) berstatus berhasil atau tidak berlaku. Induk logis = semua itu.
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
