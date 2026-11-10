# PRODUCT REQUIREMENT DOCUMENT (PRD)
## SISTEM INFORMASI MANAJEMEN (SIM) KLINIK AVICENA (v2.3)

> v2.3 (30 Okt 2026): alur apotek sebagai kasir poli, kasir lab terpisah, harga jual per batch `(HNA + PPN) × (1 + margin golongan)`, shift kasir, setoran dokter, dan satu form ERM penyakit dalam (D34–D43).
> v2.2 (29 Okt 2026): cakupan SATUSEHAT dipangkas ke Encounter, Observation (TTV dan hasil lab), Composition, ServiceRequest, dan Specimen (D33); Condition dan Medication* tidak dikirim.
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
* **Dokter:** Menginput Rekam Medis Elektronik (ERM) penyakit dalam, menetapkan biaya periksa, membuat e-Resep, dan memberikan rujukan lab/radiologi.
* **Petugas Laboratorium:** Mendaftarkan pasien lab mandiri, memproses rujukan lab, menginput hasil, mengunci hasil, dan menerima pembayaran lab.
* **Apoteker (merangkap kasir):** Mengelola inventaris obat, memvalidasi/menyerahkan e-Resep, melayani penjualan umum, menerima pembayaran pasien poli (termasuk biaya periksa), dan menyetorkan pendapatan dokter per shift.
* **Kasir:** Peran lama tidak dipakai lagi; pembayaran dipegang apoteker (poli/apotek) dan petugas lab (lab).

---

## 3. CAKUPAN MODUL & LOGIKA BISNIS

### 3.1 Modul Pendaftaran & Antrean (Onsite Only)
* **Pencarian Pasien:** Validasi berbasis **NIK (Nomor Induk Kependudukan)**. Jika NIK belum terdaftar, perawat menginput data rekam medis baru pasien.
* **Pemeriksaan TTV:** Perawat wajib menginput TTV di loket pendaftaran sebelum nomor antrean diterbitkan: tekanan darah, nadi, frekuensi napas, suhu, berat badan, tinggi badan, saturasi oksigen, gula darah, dan skala nyeri. Dokter boleh memperbarui. Untuk lab mandiri, TTV opsional.
* **Status Kunjungan:** Alur poli `terdaftar → ttv → menunggu → diperiksa → farmasi/lab → selesai`; lab mandiri `terdaftar → lab → selesai`; apotek umum `terdaftar → farmasi → selesai`. Tidak ada status `kasir`. Lab dan farmasi dapat berjalan paralel; `selesai` tercapai setelah tagihan farmasi dan lab lunas. Setiap perubahan status dicatat beserta waktu dan petugas.
* **Format Antrean Terpisah:** Antrean dipisah per jenis layanan dan otomatis di-reset menjadi `1` setiap hari. Format penomoran:
    * Poli Penyakit Dalam: `INT-001`
    * Poli Gigi: `GIG-001`
    * Poli Saraf: `SRF-001`
    * Laboratorium: `LAB-001`

### 3.2 Modul ERM & e-Resep
* **Satu Form:** Form ERM penyakit dalam dibuat otomatis saat pendaftaran dan dipakai untuk seluruh kunjungan poli penyakit dalam. Form khusus poli lain, odontogram, dan tindakan medis tidak dipakai.
* **Isi Form:** Identitas pasien, judul poli, tombol Riwayat Lab/Radiologi, tanggal/jam, Subjektif dan Objektif, TTV (terisi perawat), Riwayat Kesehatan, Pemeriksaan Fisik, diagnosis ICD-10, planning lab/radiologi/e-resep/catatan, dan cara pulang.
* **Diagnosis:** Pilihan ICD-10 (select2), satu diagnosis primer dan diagnosis sekunder tidak dibatasi.
* **Cara Pulang:** `pulang`, `dirujuk`, atau `kontrol_lagi`, diisi saat dokter menyimpan pemeriksaan dan disimpan pada kunjungan (`reg_kunjungan.kode_cara_keluar`).
* **e-Resep:** Reguler dan racikan memuat dosis terstruktur. Harga dan subtotal tidak ditampilkan saat meresepkan; harga diisi apoteker saat penyerahan dari harga batch. Copy riwayat e-resep tersedia; paket obat ditunda. Resep tidak dapat diubah setelah divalidasi apoteker.
* **Biaya Periksa:** Tarif default dari `mtr_dokter.tarif_konsultasi`, dokter boleh mengubah untuk kunjungan itu; nilai disnapshot ke `erm_pemeriksaan.tarif_konsultasi`.
* **Penyelesaian Pemeriksaan:** Waktu mulai dan selesai tercatat; data medis dikunci setelah tagihan farmasi lunas.

### 3.3 Modul Laboratorium
* **Alur Pasien Rujukan:** Pasien yang dirujuk oleh dokter spesialis akan masuk ke antrean laboratorium (`LAB-X`) secara otomatis.
* **Alur Pasien Mandiri (Walk-in):** Pasien yang datang langsung untuk tes lab (misal: Tes Darah Lengkap) didata oleh petugas lab menggunakan NIK, lalu masuk ke antrean laboratorium.
* **Pencatatan Spesimen:** Pengambilan spesimen dicatat (jenis, waktu, petugas). Status: `menunggu_diambil → diambil → diterima | ditolak`; hasil hanya dapat diinput setelah spesimen diterima, dan penolakan wajib catatan.
* **Permintaan per Pemeriksaan:** Setiap kode pemeriksaan dicatat sebagai satu baris detail permintaan; hasil per parameter menyimpan nilai rujukan dan penanda normal/rendah/tinggi.
* **Kategori:** Master pemeriksaan lab punya penanda `lab` atau `radiologi`; laporan keuangan dipisah per kategori.
* **Billing:** Hasil dikunci saat verifikasi, sebelum pembayaran. Setelah semua hasil selesai, tagihan lab dibuat terpisah dari tagihan apotek dan dibayar di kasir lab. Pasien lab mandiri membayar setelah hasil selesai.

### 3.4 Modul Inventaris Obat & Apotek (DIPERBARUI)
Modul ini menangani sisi pemenuhan resep pasien (Farmasi), penjualan retail (Apotek), serta rantai pasok logistik obat (*Supply Chain*).

* **Satu Lokasi Stok:** Seluruh stok berada di apotek; tidak ada gudang atau distribusi antar-lokasi.
* **Harga Jual:** `harga_jual = (HNA + PPN faktur) × (1 + margin golongan)`. PPN diisi per faktur; kosong berarti HNA sudah termasuk PPN. Margin per golongan di pengaturan. Harga disnapshot per batch dan tidak berubah setelahnya.
* **Komponen Tambahan:** Embalase per item reguler, jasa racik per item racikan, uang kopi dan simpanan sekali per transaksi. Nilai embalase/jasa racik/uang kopi dari pengaturan; simpanan diisi kasir dengan nilai default.
* **Kasir Poli:** Apoteker menerima pembayaran biaya periksa + obat + komponen tambahan untuk semua pasien poli, termasuk yang tidak mendapat resep. Uang kopi dan simpanan masuk kas apotek.
* **Apotek Umum:** Pembeli umum hanya membayar harga jual obat, tanpa komponen tambahan.
* **Shift:** Kasir membuka/menutup shift manual; satu akun satu shift terbuka. Setoran dokter dihitung per dokter per shift dari biaya periksa yang dibayar.
* **Alur Resep:** Validasi apoteker memeriksa ketersediaan stok; penyerahan memotong stok FEFO per batch dan mencatat batch yang dipakai. Status resep: `diterbitkan → divalidasi → diracik (jika ada) → diserahkan | batal`.
* **Pemetaan Obat:** Obat tanpa kode KFA tetap boleh diresepkan dan diserahkan. Selama MedicationRequest/MedicationDispense di luar cakupan SATUSEHAT (D33), pemetaan KFA tidak menghambat layanan.
* **Manajemen Obat Masuk (Logistik Pengadaan):**
    * Mencatat penerimaan obat dari Supplier/PBF (Pedagang Besar Farmasi).
    * Pencatatan wajib menyertakan: **Nomor Faktur, Nama Supplier, Tanggal Kedaluwarsa (Expiry Date), Nomor Bets (Batch Number), Harga Beli (HNA), dan PPN faktur (opsional)**.
    * Mendukung metode penilaian persediaan (misal: FIFO/FEFO) guna memastikan obat yang mendekati masa kedaluwarsa dikeluarkan terlebih dahulu.
* **Manajemen Retur Obat:**
    * **Retur ke Supplier:** Mengakomodasi pengembalian obat yang rusak, cacat, atau mendekati kedaluwarsa kepada PBF dengan pelacakan nomor faktur asal.
    * **Retur dari Pasien:** Mengakomodasi pengembalian obat dari pasien rawat jalan/umum (jika diizinkan regulasi internal klinik), dengan syarat obat belum dibuka/rusak, yang akan mengembalikan stok apotek dan menyesuaikan tagihan.
* **Manajemen Obat Racikan & Logika Potong Stok:**
    * Apoteker dapat membuat menu racikan dengan memilih beberapa bahan baku obat dari inventaris apotek.
    * Saat resep dikonfirmasi selesai diracik, stok masing-masing bahan baku obat terpotong secara otomatis secara *real-time* berdasarkan kuantitas komponennya.
* **Biaya Jasa Racik:** **Flat per item racikan** (tidak dihitung per jumlah bungkus/kapsul), disnapshot saat penyerahan.
* **Penyesuaian Stok (Stock Opname):** Fitur untuk mencocokkan stok fisik di apotek dengan stok di sistem secara berkala beserta pencatatan varians/selisih.

### 3.5 Modul Billing / Kasir
* **Dua Tagihan:** Satu kunjungan dapat memiliki tagihan farmasi dan tagihan lab yang terpisah. Apotek menagih pasien poli; lab menagih pemeriksaannya sendiri.
* **Tagihan Farmasi:** Biaya periksa + obat + embalase + jasa racik + uang kopi + simpanan. Biaya periksa tetap ditagih meskipun pasien tidak mendapat resep. Penjualan umum hanya berisi harga obat.
* **Tagihan Lab:** Tarif pemeriksaan setelah hasil selesai, dibayar di kasir lab.
* **Pembayaran:** Tunai atau transfer. Transfer wajib nama bank dan nama pengirim; ID transfer opsional. Diskon tidak ditampilkan.
* **Shift & Setoran:** Pembayaran hanya dapat dilakukan saat shift kasir terbuka. Setoran dokter dicatat per dokter per shift dengan metode tunai/transfer dan rincian porsi tunai/transfer.
* **Status Pembayaran:** Tagihan lunas bila total pembayaran mencukupi. Kunjungan `selesai` setelah tagihan farmasi dan lab lunas. Data ERM dikunci saat tagihan farmasi lunas; hasil lab sudah dikunci saat verifikasi.

---

## 4. TEKNIS INTEGRASI SATUSEHAT (Mekanisme Queue)

Integrasi SATUSEHAT bersifat asinkron dan tidak menghambat pelayanan. Data medis selalu disimpan lokal lebih dulu; pengiriman ke SATUSEHAT dilakukan worker di latar belakang.

### 4.1 Alur Pengiriman Data
1. Layanan domain menyimpan data ke MySQL dan memancarkan event setelah transaksi commit (`afterCommit`). Event yang tersedia: `KunjunganDidaftarkan`, `TtvDisimpan`, `PemeriksaanDimulai`, `PemeriksaanSelesai`, `ResepDiterbitkan`, `ResepDiserahkan`, `LabDiminta`, `SpesimenDiambil`, `LabDikunci`.
2. Listener SATUSEHAT membuat baris `sts_sinkronisasi` (satu baris = satu resource) dan mengantrekan `SatuSehatSyncJob` ke queue `satusehat`. Request web tidak memanggil API SATUSEHAT.
3. Worker membentuk payload FHIR, memproses resource sesuai urutan ketergantungan, dan menyimpan `ihs_id` hasil respons. Encounter memiliki siklus: `arrived` saat pendaftaran, `in-progress` saat pemeriksaan dimulai, dan `finished` saat pemeriksaan selesai beserta cara keluar.
4. Resource yang dicakup (D33): prasyarat Organization/Location/Practitioner/Patient; Encounter; ServiceRequest + Specimen + Observation hasil lab; Observation TTV; Composition (dikirim terakhir). Condition, Medication, MedicationRequest, MedicationDispense, dan DiagnosticReport tidak dikirim.
5. Jika sukses, `IHS ID` disimpan di `sts_sinkronisasi`; khusus Encounter juga tetap di `reg_kunjungan.ihs_encounter_id` sebagai bukti sinkronisasi.

### 4.2 Skema Retry & Error Handling
* Job memakai `tries = 3` dengan backoff bertahap; resource turunan menunggu induknya berhasil sebelum dikirim.
* Jika tetap gagal, baris `sts_sinkronisasi` berstatus `gagal` dan job masuk `failed_jobs`; dashboard admin menampilkan notifikasi dan menyediakan Manual Re-sync tanpa menggandakan resource yang sudah berhasil.
* Prasyarat yang belum lengkap (misalnya pemeriksaan lab tanpa LOINC atau spesimen tanpa kode SNOMED) ditandai `gagal` dengan pesan yang dapat diperbaiki, bukan error umum.

---

## 5. PERSYARATAN NON-FUNGSIONAL (Keamanan & Kinerja)

* **Keamanan Data:** NIK pasien, dokter, dan nakes disimpan terenkripsi dan dicari melalui hash (HMAC); data medis, hasil lab, dan payload SATUSEHAT tidak ditulis ke log. Akses menu dibatasi ketat menggunakan *Role-Based Access Control* (RBAC).
* **Audit & Retensi:** Data medis dan hasil lab tidak dihapus; pembatalan menggunakan status dan alasan, dan aksi penting dicatat di log aktivitas.
* **Performa UI:** Aplikasi harus memuat halaman kurang dari 2 detik pada jaringan area lokal (LAN) klinik menggunakan Bootstrap 5 yang ringan.