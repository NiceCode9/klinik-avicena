# PRODUCT REQUIREMENT DOCUMENT (PRD)
## SISTEM INFORMASI MANAJEMEN (SIM) KLINIK AVICENA (v2.0)

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
* **Pemeriksaan TTV:** Sebelum masuk ke poli, perawat wajib menginput data Tanda-Tanda Vital (Tekanan Darah, Nadi, Suhu, Berat Badan) ke dalam sistem.
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

### 3.3 Modul Laboratorium
* **Alur Pasien Rujukan:** Pasien yang dirujuk oleh dokter spesialis akan masuk ke antrean laboratorium (`LAB-X`) secara otomatis.
* **Alur Pasien Mandiri (Walk-in):** Pasien yang datang langsung untuk tes lab (misal: Tes Darah Lengkap) didata oleh petugas lab menggunakan NIK, lalu masuk ke antrean laboratorium.
* **Billing:** Hasil pemeriksaan lab dikunci di sistem, dan rincian biayanya langsung dikirim ke modul Kasir.

### 3.4 Modul Inventaris Obat & Apotek (DIPERBARUI)
Modul ini menangani sisi pemenuhan resep pasien (Farmasi), penjualan retail (Apotek), serta rantai pasok logistik obat (*Supply Chain*).

* **Apotek Umum:** Melayani pembelian obat langsung secara retail untuk masyarakat umum tanpa resep dokter.
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
* **Status Pembayaran:** Setelah kasir melakukan konfirmasi bayar, status antrean pasien diubah menjadi "Selesai" dan data rekam medis dikunci.

---

## 4. TEKNIS INTEGRASI SATUSEHAT (Mekanisme Queue)

Untuk mencegah aplikasi lambat (*freezing*) saat dokter menekan tombol simpan, sistem menggunakan pustaka/library internal dan memanfaatkan **Laravel Queue** (Asinkronus / Background Job).

### 4.1 Diagram Alur Pengiriman Data
1. Dokter/Perawat klik tombol **"Simpan Data Medis"**.
2. Aplikasi menyimpan data secara instan ke dalam basis data lokal (**MySQL**).
3. Controller memicu (*dispatch*) event job ke antrean backend: `SatuSehatSyncJob::dispatch($encounterData)`.
4. Sistem langsung memberikan respons sukses ke layar dokter, sehingga dokter bisa langsung melayani pasien berikutnya.
5. Di latar belakang, worker Laravel (`php artisan queue:work`) memproses antrean:
   * Mengemas data ke format JSON sesuai spesifikasi **FHIR (Fast Healthcare Interoperability Resources)**.
   * Melakukan request API ke SATUSEHAT Kemenkes (`Encounter` & `Condition`).
6. Jika sukses, response `IHS ID` disimpan kembali ke tabel MySQL lokal sebagai bukti sinkronisasi.

### 4.2 Skema Retry & Error Handling
* Jika koneksi internet atau server SATUSEHAT mengalami gangguan (gagal kirim), job akan dimasukkan ke dalam daftar tunggu percobaan ulang otomatis (*Automatic Retry*) sebanyak **3 kali** dengan jeda waktu (*delay*).
* Jika setelah 3 kali tetap gagal, data masuk ke tabel `failed_jobs` dan memicu notifikasi pada dashboard admin untuk dilakukan sinkronisasi ulang manual (*Manual Re-sync*).

---

## 5. PERSYARATAN NON-FUNGSIONAL (Keamanan & Kinerja)

* **Keamanan Data:** Enkripsi database menggunakan standar industri untuk perlindungan rekam medis pasien. Akses menu dibatasi ketat menggunakan *Role-Based Access Control* (RBAC).
* **Performa UI:** Aplikasi harus memuat halaman kurang dari 2 detik pada jaringan area lokal (LAN) klinik menggunakan Bootstrap 5 yang ringan.