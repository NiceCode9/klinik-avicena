# PRODUCT REQUIREMENT DOCUMENT (PRD)
## SISTEM INFORMASI MANAJEMEN (SIM) KLINIK AVICENA (v2.1)

> v2.1 (21 Okt 2026): selaras dengan keputusan D14–D22 dan `docs/satusehat-kebutuhan-data.md` — siklus Encounter, event SATUSEHAT, dosis terstruktur, pencatatan spesimen, dan penguncian data.

---

## 1. PENDAHULUAN & RINGKASAN EKSEKUTIF

### 1.1 Latar Belakang
**SIM Klinik Avicena** adalah sistem berbasis web terintegrasi yang dirancang untuk mendigitalisasi seluruh operasional klinik mulai dari pendaftaran pasien secara onsite, manajemen antrean per poli, rekam medis elektronik, manajemen farmasi/apotek, hingga sistem kasir/billing.

### 1.2 Tujuan Utama
* Mencapai operasional bebas kertas (*paperless*) untuk efisiensi administrasi.
* Memastikan kepatuhan hukum melalui integrasi wajib dengan platform **SATUSEHAT Kemenkes RI**.
* Menyediakan manajemen stok obat apotek dan laboratorium yang akurat, *real-time*, transparan, serta terdokumentasi (termasuk mutasi obat masuk dan retur).

### 1.3 Tech Stack & Arsitektur
* **Framework:** Laravel 13
* **Frontend UI:** Bootstrap 5 (Responsive Layout)
* **Database:** MySQL (Relational Database)

---

## 2. PROFIL PENGGUNA (USER PERSONA)

* **Resepsionis / Perawat:** Melakukan pendaftaran pasien *walk-in*, pemeriksaan TTV (Tanda-Tanda Vital), dan cetak nomor antrean.
* **Dokter (Spesialis Dalam, Gigi, Saraf):** Menginput Rekam Medis Elektronik (ERM), membuat e-Resep, dan memberikan rujukan internal ke Laboratorium.
* **Petugas Laboratorium:** Mendaftarkan pasien lab mandiri, memproses rujukan lab dari dokter, dan menginput hasil pemeriksaan.
* **Apoteker / Petugas Farmasi:** Mengelola inventaris obat (stok masuk, opname, retur), memvalidasi e-Resep, meracik obat dengan kalkulasi stok otomatis, serta melayani pembelian obat umum.
* **Kasir:** Menerima billing otomatis dari poli, lab, maupun apotek, serta memproses pembayaran pasien.

---

## 3. CAKUPAN MODUL & LOGIKA BISNIS

### 3.1 Modul Pendaftaran & Antrean (Onsite Only)
* **Pencarian Pasien:** Validasi berbasis **NIK (Nomor Induk Kependudukan)**. Jika NIK belum terdaftar, perawat menginput data rekam medis baru pasien.
* **Pemeriksaan TTV:** Sebelum masuk ke poli, perawat wajib menginput data Tanda-Tanda Vital (Tekanan Darah, Nadi, Suhu, Berat Badan) ke dalam sistem. Untuk lab mandiri, TTV bersifat opsional.
* **Status Kunjungan:** Alur status `terdaftar → ttv → menunggu → diperiksa → lab/farmasi → kasir → selesai` (lab dan farmasi dapat berjalan paralel), atau `batal` dengan alasan. Setiap perubahan status dicatat beserta waktu dan petugas; waktu datang, mulai, dan selesai pemeriksaan tersimpan.
* **Format Antrean Terpisah:** Antrean dipisah per jenis layanan dan otomatis di-reset menjadi `1` setiap hari. Format penomoran:
    * Poli Penyakit Dalam: `INT-001`
    * Poli Gigi: `GIG-001`
    * Poli Saraf: `SRF-001`
    * Laboratorium: `LAB-001`

### 3.2 Modul ERM & e-Resep (Spesialis & Umum)
* **Metode Input:** Berbasis **SOAP** (*Subjective, Objective, Assessment, Plan*) dan kodefikasi penyakit **ICD-10**.
* **Referensi Form Medis Khusus:**
    * *Poli Penyakit Dalam:* Form riwayat penyakit kronis, status metabolik, dan review sistem organ.
    * *Poli Gigi:* Integrasi grafik **Odontogram Digital** untuk mencatat kondisi per gigi pasien.
    * *Poli Saraf:* Form evaluasi motorik, sensorik, dan pemeriksaan saraf kranial.
* **Output Dokter:** Dokter dapat menerbitkan e-Resep langsung ke Farmasi atau mengirim permintaan Pemeriksaan Lab internal secara digital.
* **e-Resep:** Resep reguler dan racikan memuat dosis terstruktur (dosis, satuan, frekuensi, periode, rute, lama hari, aturan tambahan); teks aturan pakai tetap ditampilkan. Resep tidak dapat diubah setelah divalidasi apoteker.
* **Penyelesaian Pemeriksaan:** Dokter wajib mengisi cara keluar saat menyimpan final; waktu mulai dan selesai pemeriksaan tercatat.

### 3.3 Modul Laboratorium
* **Alur Pasien Rujukan:** Pasien yang dirujuk oleh dokter spesialis akan masuk ke antrean laboratorium (`LAB-X`) secara otomatis.
* **Alur Pasien Mandiri (Walk-in):** Pasien yang datang langsung untuk tes lab (misal: Tes Darah Lengkap) didata oleh petugas lab menggunakan NIK, lalu masuk ke antrean laboratorium.
* **Pencatatan Spesimen:** Pengambilan spesimen dicatat (jenis, waktu, petugas). Status: `menunggu_diambil → diambil → diterima | ditolak`; hasil hanya dapat diinput setelah spesimen diterima, dan penolakan wajib catatan.
* **Permintaan per Pemeriksaan:** Setiap kode pemeriksaan dicatat sebagai satu baris detail permintaan; hasil per parameter menyimpan nilai rujukan dan penanda normal/rendah/tinggi.
* **Billing:** Hasil pemeriksaan lab dikunci di sistem, dan rincian biayanya langsung dikirim ke modul Kasir.

### 3.4 Modul Inventaris Obat & Apotek (DIPERBARUI)
Modul ini menangani sisi pemenuhan resep pasien (Farmasi), penjualan retail (Apotek), serta rantai pasok logistik obat (*Supply Chain*).

* **Apotek Umum:** Melayani pembelian obat langsung secara retail untuk masyarakat umum tanpa resep dokter.
* **Alur Resep:** Validasi apoteker memeriksa ketersediaan stok; penyerahan memotong stok FEFO per batch dan mencatat batch yang dipakai. Status resep: `diterbitkan → divalidasi → diracik (jika ada) → diserahkan | batal`.
* **Pemetaan Obat:** Obat tanpa kode KFA tetap boleh diresepkan, tetapi ditandai "belum terpetakan" dan tidak dikirim ke SATUSEHAT sampai dipetakan.
* **Manajemen Obat Masuk (Logistik Pengadaan):**
    * Mencatat penerimaan obat dari Supplier/PBF (Pedagang Besar Farmasi).
    * Pencatatan wajib menyertakan: **Nomor Faktur, Nama Supplier, Tanggal Kedaluwarsa (Expiry Date), Nomor Bets (Batch Number), dan Harga Beli**.
    * Mendukung metode penilaian persediaan (misal: FIFO/FEFO) guna memastikan obat yang mendekati masa kedaluwarsa dikeluarkan terlebih dahulu.
* **Manajemen Retur Obat:**
    * **Retur ke Supplier:** Mengakomodasi pengembalian obat yang rusak, cacat, atau mendekati kedaluwarsa kepada PBF dengan pelacakan nomor faktur asal.
    * **Retur dari Pasien:** Mengakomodasi pengembalian obat dari pasien rawat jalan/umum (jika diizinkan regulasi internal klinik), dengan syarat obat belum dibuka/rusak, yang akan mengembalikan stok apotek dan menyesuaikan tagihan.
* **Manajemen Obat Racikan & Logika Potong Stok:**
    * Apoteker dapat membuat menu racikan dengan memilih beberapa bahan baku obat dari inventaris apotek.
    * Saat resep dikonfirmasi selesai diracik, stok masing-masing bahan baku obat terpotong secara otomatis secara *real-time* berdasarkan kuantitas komponennya.
* **Biaya Jasa Racik:** Ditetapkan sebagai **Biaya Flat** per resep racikan (tidak dihitung per jumlah bungkus/kapsul hasil akhir).
* **Penyesuaian Stok (Stock Opname):** Fitur untuk mencocokkan stok fisik di apotek dengan stok di sistem secara berkala beserta pencatatan varians/selisih.

### 3.5 Modul Billing / Kasir
* **Konsolidasi Tagihan:** Kasir menerima total tagihan otomatis yang menggabungkan seluruh komponen tindakan:
    * Biaya Konsultasi Dokter Spesialis
    * Biaya Tindakan Medis (jika ada)
    * Biaya Pemeriksaan Laboratorium
    * Biaya Obat (Reguler & Racikan) + Jasa Peracikan Flat
* **Status Pembayaran:** Setelah kasir mengonfirmasi pembayaran lunas, status kunjungan menjadi "selesai"; data rekam medis dan hasil lab dikunci.

---

## 4. TEKNIS INTEGRASI SATUSEHAT (Mekanisme Queue)

Integrasi SATUSEHAT bersifat asinkron dan tidak menghambat pelayanan. Data medis selalu disimpan lokal lebih dulu; pengiriman ke SATUSEHAT dilakukan worker di latar belakang.

### 4.1 Alur Pengiriman Data
1. Layanan domain menyimpan data ke MySQL dan memancarkan event setelah transaksi commit (`afterCommit`). Event yang tersedia: `KunjunganDidaftarkan`, `TtvDisimpan`, `PemeriksaanDimulai`, `PemeriksaanSelesai`, `ResepDiterbitkan`, `ResepDiserahkan`, `LabDiminta`, `SpesimenDiambil`, `LabDikunci`.
2. Listener SATUSEHAT membuat baris `sts_sinkronisasi` (satu baris = satu resource) dan mengantrekan `SatuSehatSyncJob` ke queue `satusehat`. Request web tidak memanggil API SATUSEHAT.
3. Worker membentuk payload FHIR, memproses resource sesuai urutan ketergantungan, dan menyimpan `ihs_id` hasil respons. Encounter memiliki siklus: `arrived` saat pendaftaran, `in-progress` saat pemeriksaan dimulai, dan `finished` saat pemeriksaan selesai beserta Condition dan cara keluar.
4. Resource yang dicakup: prasyarat Organization/Location/Practitioner/Patient; Encounter + Condition; MedicationRequest + MedicationDispense (data Medication menyatu, verifikasi saat implementasi); ServiceRequest + Specimen + Observation lab; Observation TTV; Composition (dikirim terakhir). DiagnosticReport menunggu verifikasi.
5. Jika sukses, `IHS ID` disimpan di `sts_sinkronisasi` (Encounter/Condition tetap di kolom domain yang sudah ada) sebagai bukti sinkronisasi.

### 4.2 Skema Retry & Error Handling
* Job memakai `tries = 3` dengan backoff bertahap; resource turunan menunggu induknya berhasil sebelum dikirim.
* Jika tetap gagal, baris `sts_sinkronisasi` berstatus `gagal` dan job masuk `failed_jobs`; dashboard admin menampilkan notifikasi dan menyediakan Manual Re-sync tanpa menggandakan resource yang sudah berhasil.
* Prasyarat yang belum lengkap (misalnya obat tanpa KFA atau lab tanpa LOINC/spesimen) ditandai `gagal` dengan pesan yang dapat diperbaiki, bukan error umum.

---

## 5. PERSYARATAN NON-FUNGSIONAL (Keamanan & Kinerja)

* **Keamanan Data:** NIK pasien, dokter, dan nakes disimpan terenkripsi dan dicari melalui hash (HMAC); data medis, hasil lab, dan payload SATUSEHAT tidak ditulis ke log. Akses menu dibatasi ketat menggunakan *Role-Based Access Control* (RBAC).
* **Audit & Retensi:** Data medis dan hasil lab tidak dihapus; pembatalan menggunakan status dan alasan, dan aksi penting dicatat di log aktivitas.
* **Performa UI:** Aplikasi harus memuat halaman kurang dari 2 detik pada jaringan area lokal (LAN) klinik menggunakan Bootstrap 5 yang ringan.