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
| D13 | Data medis & hasil lab dikunci setelah pembayaran lunas. | PRD §3.5 |
| D14 | Model autentikasi tetap `App\Models\User` (extends `Authenticatable`, tabel `sys_pengguna`), bukan `App\Models\Sys\Pengguna`. | Mengikuti skeleton Laravel; tabel `sys_` lain tetap di namespace `Sys` |

## Pertanyaan terbuka (tanyakan ke pemilik proyek sebelum implementasi final)
| # | Pertanyaan | Default sementara |
|---|---|---|
| Q1 | Apakah dokter lab memungut jasa (komponen tagihan sendiri)? | Tidak ada, hanya biaya pemeriksaan lab |
| Q2 | `erm_resep_racikan_bahan.jumlah_bahan` = total yang dipakai per item racikan atau per bungkus/kapsul? | Total per item racikan |
| Q3 | Jasa racik flat dikenakan per resep atau per item racikan? Harga obat racikan = Σ harga bahan? | Per item racikan; Σ(jumlah bahan × harga jual) |
| Q4 | Retur dari pasien setelah tagihan lunas: refund tunai, kredit, atau tidak diizinkan? Batas waktu? | Tidak diizinkan setelah lunas sampai ada keputusan |
| Q5 | Status kunjungan saat lab & farmasi berjalan paralel | `StatusKunjunganService` menghitung ulang (lihat business-rules §1) |
| Q6 | Koreksi data medis/lab setelah terkunci (addendum? izin khusus + audit?) | Tidak ada koreksi; terkunci mutlak |
| Q7 | TTV untuk lab mandiri wajib/opsional? | Opsional |
| Q8 | Format `no_rekam_medis`, `no_kunjungan`, `no_resep`, `no_tagihan`, dll. | `RM-000001`, `KJ-YYYYMMDD-0001`, dst. (seq harian) |
| Q9 | Pembayaran non-tunai/asuransi/BPJS, diskon, cicilan? PRD tidak menyebut. | Hanya metode di `mtr_metode_bayar`; diskon manual oleh kasir |
| Q10 | Laporan apa saja yang dibutuhkan (kasir harian, stok/kedaluwarsa, kunjungan, 10 penyakit terbanyak)? | Kasir harian + kartu stok dulu |
| Q11 | Perangkat cetak antrean/kwitansi (thermal merk/ukuran) & layar TV antrean? | Cetak via browser (CSS print) |
| Q12 | Obat narkotika/psikotropika perlu pencatatan/pelaporan khusus? | Belum |
| Q13 | Kebijakan retensi & arsip data rekam medis sesuai regulasi | Tidak ada penghapusan data medis; verifikasi regulasi |
| Q14 | Satu pasien boleh punya >1 kunjungan aktif di hari yang sama (poli berbeda)? | Boleh, tiap kunjungan mandiri |
