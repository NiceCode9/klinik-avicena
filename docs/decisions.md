# Catatan Keputusan & Pertanyaan Terbuka

Perbarui file ini setiap ada keputusan baru. Agen AI wajib memeriksanya sebelum menambah package/mengubah arah.

## Keputusan (ADR ringkas)
| # | Keputusan | Alasan |
|---|---|---|
| D1 | Monolit Laravel 13, Blade server-rendered + Bootstrap 5 + AdminLTE 4. Tanpa SPA/Livewire/Inertia. | Sederhana, cepat di LAN, mudah dirawat |
| D2 | Tabel & kolom bahasa Indonesia dengan prefix modul (`sys_ mtr_ reg_ erm_ lab_ apt_ bil_ sts_`). `created_at/updated_at/deleted_at` serta `password` & `remember_token` (autentikasi) tetap nama bawaan Laravel 13. | Permintaan pemilik proyek; kompatibel dengan auth bawaan |
| D3 | MySQL 8 sebagai satu-satunya DB, termasuk untuk tes. | JSON/ENUM/CHECK/row lock |
| D4 | `nik` terenkripsi + `nik_hash` (HMAC) untuk pencarian & unik. | Lindungi data pribadi tanpa kehilangan pencarian |
| D5 | Stok per batch + buku besar `apt_mutasi_stok`; pengeluaran FEFO. | Kedaluwarsa, retur, audit |
| D6 | Antrean harian lewat `reg_penghitung_antrean` + lock + unique index. | Cegah nomor ganda |
| D7 | Menu hibrida: `config/menu.php` → `sys_menu` (`menu:sinkron`); hak per peran di `sys_hak_akses`; middleware `izin`. Tanpa spatie/permission. | Sidebar dinamis, hak dapat diatur admin, kode tetap di git |
| D8 | Lab mandiri memakai layanan pendaftaran bersama; petugas lab memilih dokter lab (`lab_permintaan.dokter_id`) dari semua dokter aktif. | Satu aturan NIK/antrean; dokter lab terpisah dari perujuk |
| D9 | SATUSEHAT asinkron lewat queue, retry 3x, `failed_jobs` + Manual Re-sync. | PRD §4 |
| D10 | Harga/tarif disnapshot ke baris transaksi. | Perubahan master tak mengubah transaksi lama |
| D11 | Soft delete hanya untuk master & pengguna; transaksi/medis tidak dihapus. | Integritas & audit |
| D12 | Setelah rilis pertama, migration adalah sumber kebenaran skema (bukan `schema.sql`). | Hindari drift |
| D13 | Data medis & hasil lab dikunci setelah pembayaran lunas. ~~Hasil lab: lihat D40 (dikunci saat verifikasi, bukan saat bayar).~~ | PRD §3.5 |
| D14 | Layanan domain memancarkan event `afterCommit`: `KunjunganDidaftarkan`, `TtvDisimpan`, `PemeriksaanDimulai`, `PemeriksaanSelesai`, `ResepDiterbitkan`, `ResepDiserahkan`, `LabDiminta`, `SpesimenDiambil`, `LabDikunci`. Listener SATUSEHAT (Fase 7) membuat `sts_sinkronisasi` + job. | Encounter punya siklus (arrived → in-progress → finished); modul klinis bisa selesai tanpa SATUSEHAT |
| D15 | ~~Cakupan SATUSEHAT: prasyarat; Tahap A Encounter+Condition; B MedicationRequest/Dispense/Medication; C ServiceRequest/Specimen/Observation lab + Observation TTV; D Composition. DiagnosticReport menunggu verifikasi. Procedure/alergi/ClinicalImpression ditunda.~~ **Digantikan D33.** | Akun SATUSEHAT klinik + analisis kebutuhan data |
| D16 | Observation TTV ikut dikirim. | Data sudah dikumpulkan; pelengkap resume medis |
| D17 | Spesimen dicatat di tabel `lab_spesimen`; `lab_permintaan_detail.spesimen_id` menautkan. | `Specimen.type` (SNOMED) wajib |
| D18 | Tenaga kesehatan non-dokter di `mtr_nakes` (nik terenkripsi, `ihs_practitioner_id`, `pengguna_id` opsional). | Apoteker/perawat/petugas lab butuh IHS Practitioner; akun login terpisah dari data nakes |
| D19 | Lokasi non-poli (apotek) di `mtr_lokasi` (seed `APT` lewat `PemetaanSatuSehatSeeder`). | Lokasi dispense obat |
| D20 | `ihs_id` resource baru disimpan di `sts_sinkronisasi` (kolom `aksi`, `sub_kunci`, `bergantung_pada_id`); bukan kolom baru di tabel domain. | Menghindari kolom berlebih; mendukung ketergantungan & banyak resource per referensi |
| D21 | Model autentikasi tetap `App\Models\User` (extends `Authenticatable`, tabel `sys_pengguna`), bukan `App\Models\Sys\Pengguna`. | Mengikuti skeleton Laravel; tabel `sys_` lain tetap di namespace `Sys` |
| D22 | Status spesimen: `menunggu_diambil → diambil → diterima | ditolak`. Hasil lab hanya diinput setelah `diterima`; `ditolak` wajib `catatan`. | Menjawab Q24 (21 Okt 2026); menjaga kualitas spesimen & jejak audit |
| D23 | Paket `ivanwilliammd/satusehat-integration` dipin `4.14.1` dan hanya boleh dipakai sebagai builder payload generik lewat satu adapter `App\Services\SatuSehat\PembentukFhir`. Queue, token, log, retry, dan klien terminologi paket tidak dipakai; domain tidak mengimpor paket. | Batas satu file; paket bisa dilepas tanpa mengubah domain. Rancangan: `satusehat-fondasi.md` §1 |
| D24 | ~~KFA: `mtr_obat.kode_kfa` = produk **aktual** (93xxxxxx) untuk MedicationDispense; `kode_kfa_virtual` untuk MedicationRequest hanya bila halaman resmi mengonfirmasi. Tanpa fallback diam-diam; laporan memisahkan status keduanya.~~ Tidak berlaku selama D33 (Medication* di luar cakupan). | Mencegah kode granularitas salah terkirim; verifikasi resmi saat 7B.1 |
| D25 | `mtr_satuan.kode_ucum` menyimpan kode UCUM (migration baru). `erm_resep_item.satuan_dosis` tetap nama satuan pilihan dari master; builder me-resolve ke UCUM. UCUM kosong = gagal permanen. | Satu pemetaan per satuan; tidak menebak `{nama}` |
| D26 | KFA disimpan di master `mtr_obat`, bukan di `apt_stok_batch`. | KFA identitas produk; batch hanya untuk FEFO/kedaluwarsa |
| D27 | Composition menunggu induk yang masih `menunggu/diproses`; induk `gagal` tidak memblokir, Composition dikirim dengan resource yang tersedia dan alasannya dicatat. | Resume medis tidak tertahan selamanya; detail final saat 7D.1 |
| D33 | Cakupan SATUSEHAT direvisi: hanya Encounter, Observation (TTV dan hasil lab), Composition, ServiceRequest, dan Specimen. Condition, Medication, MedicationRequest, MedicationDispense, dan DiagnosticReport keluar dari cakupan. Prasyarat Organization, Location, Practitioner, dan Patient tetap dikirim. | Instruksi pemilik 29 Okt 2026; resource yang dikirim dibatasi |
| D34 | Stok hanya di satu lokasi: apotek. Tidak ada gudang, permintaan/distribusi, atau stok per lokasi. Model stok tetap per batch + FEFO. | Alur klinik kecil; barang langsung diterima apotek |
| D35 | `harga_jual = (HNA + PPN faktur) × (1 + margin golongan)`, dibulatkan tidak (disimpan apa adanya). `apt_penerimaan.ppn_persen` nullable; kosong berarti HNA sudah termasuk PPN. Margin per golongan disimpan di `sys_pengaturan`. Harga disnapshot per batch; `mtr_obat.harga_jual` dihapus. | Rumus dari pemilik; PPN menempel di faktur, bukan satu angka global |
| D36 | Tagihan farmasi = biaya periksa + harga obat + embalase + jasa racik + uang kopi + simpanan. Embalase per item reguler (racikan tidak); jasa racik per item racikan; uang kopi & simpanan sekali per transaksi. Uang kopi dan simpanan masuk kas apotek. Penjualan umum hanya harga jual. | Instruksi pemilik 30 Okt 2026 |
| D37 | Apotek adalah loket pembayaran semua pasien poli, termasuk tanpa resep. Lab punya kasir sendiri. Satu kunjungan dapat punya dua tagihan: `bil_tagihan.jenis = farmasi` dan `lab`; unik `(kunjungan_id, jenis)`. | Biaya periksa selalu dibayar di apotek; lab terpisah |
| D38 | Shift kasir dibuka/ditutup manual per akun. Satu akun hanya boleh punya satu shift terbuka. Shift yang lupa ditutup tidak memblokir kasir lain, ditandai di dasbor, dan dapat ditutup paksa admin dengan alasan. Pembayaran hanya boleh saat shift terbuka. | Menghindari layanan berhenti karena satu kasir lupa menutup shift |
| D39 | Setoran dokter dihitung per dokter per shift dari biaya periksa yang sudah dibayar pada shift itu, apa pun metode bayar pasien. Setoran dicatat dengan metode tunai/transfer, referensi bila transfer, dan rincian porsi tunai/transfer. | Uang yang disetor berbeda dari cara pasien membayar |
| D40 | Hasil lab dikunci saat diverifikasi petugas/dokter lab, sebelum pembayaran. Tagihan lab dibuat setelah hasil selesai dan dibayar di kasir lab. | Pasien membayar setelah hasil selesai; data tidak berubah setelah verifikasi |
| D41 | Satu form ERM untuk poli penyakit dalam, dibuat otomatis saat pendaftaran. Dokter poli dipilih saat pendaftaran dan ERM otomatis memakai dokter itu. TTV diisi perawat di pendaftaran dan boleh diperbarui dokter. Diagnosis memakai ICD-10 select2 (satu primer, sekunder tak terbatas). Cara pulang: `pulang`, `dirujuk`, `kontrol_lagi`, disimpan di `reg_kunjungan.kode_cara_keluar`/`nama_cara_keluar`. Form khusus poli lain, odontogram, dan tindakan tidak dipakai. | Form contoh pemilik; fokus satu poli |
| D42 | `reg_kunjungan.status` tidak lagi memakai `kasir`. `selesai` hanya tercapai bila tagihan farmasi lunas dan tagihan lab lunas/tidak ada; status `farmasi`/`lab` ditentukan `StatusKunjunganService::sinkron`. | Dua tagihan yang bisa dibayar dengan urutan bebas |
| D43 | Pembayaran menerima tunai atau transfer. Transfer wajib nama bank dan nama pengirim; ID transfer opsional (`bil_pembayaran.no_referensi`). Diskon tidak ditampilkan di UI. | Instruksi pemilik 30 Okt 2026 |

## Pertanyaan terbuka (tanyakan ke pemilik proyek sebelum implementasi final)
| # | Pertanyaan | Default sementara |
|---|---|---|
| Q1 | Apakah dokter lab memungut jasa (komponen tagihan sendiri)? | Tidak ada, hanya biaya pemeriksaan lab |
| Q2 | `erm_resep_racikan_bahan.jumlah_bahan` = total yang dipakai per item racikan atau per bungkus/kapsul? | Total per item racikan |
| Q3 | ~~Jasa racik flat dikenakan per resep atau per item racikan? Harga obat racikan = Σ harga bahan?~~ Terjawab D36: per item racikan; harga racikan = Σ(jumlah bahan × harga jual batch) + jasa racik. | — |
| Q4 | Retur dari pasien setelah tagihan lunas: refund tunai, kredit, atau tidak diizinkan? Batas waktu? | Tidak diizinkan setelah lunas sampai ada keputusan |
| Q5 | ~~Status kunjungan saat lab & farmasi berjalan paralel~~ Terjawab D42: `sinkron()` memilih `farmasi`/`lab`/`selesai` berdasarkan tagihan yang belum lunas. | — |
| Q6 | Koreksi data medis/lab setelah terkunci (addendum? izin khusus + audit?) | Tidak ada koreksi; terkunci mutlak |
| Q7 | TTV untuk lab mandiri wajib/opsional? | Opsional |
| Q8 | Format `no_rekam_medis`, `no_kunjungan`, `no_resep`, `no_tagihan`, dll. | `RM-000001`, `KJ-YYYYMMDD-0001`, dst. (seq harian) |
| Q9 | ~~Pembayaran non-tunai/asuransi/BPJS, diskon, cicilan?~~ Terjawab D43: tunai/transfer (nama bank, nama pengirim, ID transfer opsional); diskon tidak ditampilkan; asuransi/BPJS/cicilan di luar lingkup. | — |
| Q10 | ~~Laporan apa saja yang dibutuhkan?~~ Terjawab D36–D39: penjualan apotek (poli/umum, per golongan), HPP & laba kotor, rekap shift & setoran dokter, laporan lab dipisah lab/radiologi, laporan harian per shift. | — |
| Q11 | Perangkat cetak antrean/kwitansi (thermal merk/ukuran) & layar TV antrean? | Cetak via browser (CSS print) |
| Q12 | Obat narkotika/psikotropika perlu pencatatan/pelaporan khusus? | Belum |
| Q13 | Kebijakan retensi & arsip data rekam medis sesuai regulasi | Tidak ada penghapusan data medis; verifikasi regulasi |
| Q14 | Satu pasien boleh punya >1 kunjungan aktif di hari yang sama (poli berbeda)? | Boleh, tiap kunjungan mandiri |
| Q15–Q20 | Terjawab — lihat §6 `satusehat-kebutuhan-data.md`. | — |
| Q21 | DiagnosticReport perlu atau tidak (default: tunda sampai halaman resmi dibaca). | — |
| Q22 | section wajib & kode `Composition` (default: ikuti halaman resmi saat Tahap D). | — |
| Q23 | kapan Encounter `in-progress` dikirim bila dokter lupa menekan "mulai" (default: `PemeriksaanDimulai` dipicu otomatis saat dokter membuka form pemeriksaan pertama kali). | — |
| Q24 | (Usulan) Pemeriksaan tanpa spesimen: boleh simpan hasil langsung atau wajib catat spesimen dulu (D22)? | Default sementara: boleh jika `spesimen_id = null` (TODO(Q9) di `LabService::simpanHasil`); finalkan sebelum rilis Lab. |
