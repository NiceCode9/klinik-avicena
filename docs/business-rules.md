# Aturan Bisnis — SIM Klinik Avicena

Sumber: `PRD.md` + keputusan di `decisions.md`. Item bertanda **[USULAN]** adalah asumsi saya yang belum dikonfirmasi; lihat "Pertanyaan terbuka" di `decisions.md` sebelum mengimplementasikannya final.

## 1. Status kunjungan (`reg_kunjungan.status`)
| Dari | Ke | Syarat |
|---|---|---|
| terdaftar | ttv | TTV tersimpan (`reg_ttv`) |
| ttv | menunggu | antrean poli dibuat (`reg_antrean`) |
| menunggu | diperiksa | dokter memanggil/mulai |
| diperiksa | lab | ada `erm_rujukan_lab` |
| diperiksa | farmasi | ada `erm_resep` |
| diperiksa | kasir | tidak ada lab & resep, pemeriksaan disimpan |
| lab | farmasi / kasir | `lab_permintaan.dikunci = 1` |
| farmasi | kasir | resep berstatus `diserahkan` |
| kasir | selesai | pembayaran lunas dikonfirmasi |
| (apa pun sebelum kasir) | batal | alasan wajib; data medis tidak dihapus |

- Perpindahan status HANYA lewat Service yang memvalidasi tabel di atas. Dilarang `update(['status' => ...])` langsung dari controller.
- Jenis `lab_mandiri` dan `apotek_umum` tidak melewati ttv/poli: `terdaftar → lab → kasir → selesai` dan `terdaftar → kasir → selesai`.
- **[USULAN] Lab dan farmasi bisa berjalan paralel** (pasien dirujuk lab sekaligus diberi resep). Satu kolom status tidak bisa mewakili paralel, jadi: status menjadi `kasir` ketika SEMUA unit terkait selesai (semua `lab_permintaan` terkunci DAN semua `erm_resep` diserahkan/batal). `StatusKunjunganService::sinkron($kunjungan, $oleh)` dipanggil setiap unit selesai dan menghitung ulang status.
- `sinkron()` mengunci `reg_kunjungan`, lalu `lab_permintaan` dan `erm_resep` terkait dengan `FOR UPDATE` agar membaca data terbaru (bukan snapshot lama transaksi pemanggil). Service lab/farmasi harus mengunci baris `reg_kunjungan` sebelum mengubah data unitnya agar urutan lock konsisten dan tidak deadlock.
- setiap perpindahan status mencatat baris `reg_kunjungan_riwayat_status` (status, waktu, pengguna). `waktu_datang` terisi saat dibuat, `waktu_mulai` saat `diperiksa`/mulai dilayani, `waktu_selesai` saat selesai pemeriksaan. Cara keluar (`kode_cara_keluar`) wajib diisi saat pemeriksaan difinalkan.

## 2. Pasien & NIK
- Pencarian pasien oleh NIK memakai `nik_hash = HMAC-SHA256(nik, NIK_HMAC_KEY)`. `nik` disimpan terenkripsi (cast `encrypted`).
- NIK harus 16 digit angka; satu NIK = satu pasien (unique `nik_hash`). Jika NIK belum ada, perawat/petugas mendaftarkan pasien baru (data rekam medis dasar).
- `no_rekam_medis` unik, dibuat otomatis oleh `PendaftaranService` **[format: lihat pertanyaan terbuka]**.
- Peran `petugas_lab` boleh menambah pasien baru tetapi tidak mengubah identitas pasien yang sudah ada.

## 3. Antrean
- Satu penghitung per (poli, tanggal) di `reg_penghitung_antrean`; reset otomatis karena kunci memuat tanggal.
- `AntreanService::ambilNomor()`: transaksi → ambil/buat baris penghitung → `lockForUpdate()` → `nomor_terakhir + 1` → buat `reg_antrean` dengan `nomor_antrean = KODE-` + nomor 3 digit (`INT-001`).
- Unique index `(poli_id, tanggal, nomor_urut)` sebagai jaring pengaman terakhir.
- Kode: Penyakit Dalam `INT`, Gigi `GIG`, Saraf `SRF`, Lab `LAB`. Rujukan lab membuat antrean `LAB-xxx` baru pada kunjungan yang sama (satu kunjungan boleh punya >1 antrean).
- Status antrean: `menunggu → dipanggil → dilayani → selesai | batal`.
- `batal` boleh dari `menunggu`, `dipanggil`, atau `dilayani`. `selesai` dan `batal` terminal dan tidak dapat diubah lagi.
- `waktu_panggil` diisi saat `panggil`; `waktu_selesai` diisi saat `selesai` atau `batal`.
- Transisi di luar alur melempar `StatusAntreanTidakValidException`.

## 4. TTV
- Wajib untuk kunjungan poli sebelum masuk ke poli: tekanan darah (sistolik/diastolik), nadi, suhu, berat badan. Satu TTV per kunjungan.
- Lab mandiri: **[USULAN] TTV opsional** (PRD tidak mewajibkan).

## 5. Rekam medis (ERM)
- SOAP (`erm_pemeriksaan`) + diagnosis ICD-10 (`erm_diagnosis`, minimal satu diagnosis primer saat disimpan final).
- Form khusus per poli: Penyakit Dalam (`erm_detail_penyakit_dalam`), Saraf (`erm_detail_saraf`), Gigi (`erm_odontogram`: satu baris per gigi/permukaan/kondisi, notasi FDI).
- Dokter hanya melihat/mengubah pemeriksaan yang ia buat sampai terkunci (Policy). Dokter lain boleh MELIHAT riwayat pasien (hak `lihat`).
- Setelah pembayaran lunas: `dikunci = 1`, `dikunci_pada` terisi. Pemeriksaan terkunci tidak bisa diubah/dihapus. Koreksi setelah terkunci: **belum diputuskan** (lihat pertanyaan terbuka).
- Perubahan data medis memancarkan event domain `afterCommit`; listener SATUSEHAT membuat `sts_sinkronisasi` + job (lihat `satusehat.md`).

## 6. e-Resep
- Status: `diterbitkan → divalidasi → diracik (jika ada racikan) → diserahkan | batal`.
- Item `jenis = reguler` wajib `obat_id`; item `racikan` wajib punya bahan di `erm_resep_racikan_bahan` (bisa disalin dari template `mtr_racikan`).
- Validasi apoteker memeriksa ketersediaan stok (hanya batch belum kedaluwarsa).
- item wajib memuat dosis terstruktur (`dosis`, `satuan_dosis`, `frekuensi`, `periode`, `satuan_periode`, `kode_rute`, `lama_hari`); `aturan_pakai` teks tetap disimpan untuk tampilan. KFA tidak lagi menjadi prasyarat SATUSEHAT karena MedicationRequest/MedicationDispense di luar cakupan (D33); obat tanpa KFA tetap bisa diresepkan dan diserahkan.

## 7. Laboratorium
- **Rujukan**: `erm_rujukan_lab` → otomatis membuat `lab_permintaan` (`sumber = rujukan`) + antrean `LAB-xxx`.
- **Mandiri**: petugas lab mendaftarkan pasien lewat layanan pendaftaran bersama (cari NIK atau daftar baru) → kunjungan `lab_mandiri` → antrean `LAB-xxx` → `lab_permintaan` (`sumber = mandiri`).
- **Dokter lab (`lab_permintaan.dokter_id`)**: dipilih petugas lab dari daftar semua dokter aktif (dokter berpoli LAB diurutkan paling atas). Mandiri: wajib saat pendaftaran. Rujukan: wajib sebelum hasil dikunci. Dokter perujuk berbeda dan tetap di `erm_rujukan_lab.dokter_id`.
- Hasil per parameter (`lab_hasil`) menyimpan snapshot satuan & nilai rujukan, penanda otomatis (normal/rendah/tinggi) dari batas min/max.
- Kunci hasil (`dikunci = 1`) → tarif dari `lab_permintaan_detail` masuk tagihan. Setelah dikunci tidak bisa diubah.
- pengambilan spesimen dicatat di `lab_spesimen` (jenis dari `mtr_pemeriksaan_lab.kode_spesimen`, waktu, petugas) sebelum hasil diinput; `lab_permintaan_detail.spesimen_id` terisi. Permintaan dipecah menjadi **satu baris detail per kode pemeriksaan** (sesuai satu ServiceRequest per kode).
- Status spesimen: `menunggu_diambil → diambil → diterima | ditolak`. Hasil hanya boleh diinput setelah status `diterima`; `ditolak` wajib mengisi `catatan`.

## 8. Apotek & inventaris

### 8.1 Model stok
- Stok disimpan **per batch** (`apt_stok_batch`: obat + nomor bets + tanggal kedaluwarsa + harga beli + `jumlah_sisa`).
- Setiap perubahan stok = satu baris di `apt_mutasi_stok` (buku besar): jenis, jumlah (+ masuk / − keluar), `saldo_setelah` (saldo batch), referensi sumber.
- **Invarian**: untuk setiap batch, `jumlah_sisa == SUM(apt_mutasi_stok.jumlah)`. Stok obat = jumlah `jumlah_sisa` batch yang belum kedaluwarsa.
- `jumlah_sisa` TIDAK PERNAH diubah langsung; hanya `StokService`.

### 8.2 Pengeluaran FEFO (`StokService::kurangi`)
```
DB::transaction:
  batch = apt_stok_batch WHERE obat_id=? AND jumlah_sisa>0 AND tanggal_kedaluwarsa >= today
          ORDER BY tanggal_kedaluwarsa ASC, id ASC  FOR UPDATE
  ambil dari batch pertama sampai jumlah terpenuhi (boleh lintas batch)
  jika total batch < diminta -> lempar StokTidakCukupException (tidak ada perubahan sama sekali)
  tiap batch: jumlah_sisa -= ambil; insert apt_mutasi_stok (jumlah negatif, saldo_setelah, referensi)
  kembalikan daftar [batch_id => jumlah] untuk dicatat di apt_pengeluaran_resep / detail penjualan
```
Batch kedaluwarsa tidak pernah dikeluarkan; ditangani lewat retur supplier atau opname minus.

### 8.3 Obat masuk
Satu faktur (`apt_penerimaan`: no faktur, supplier, tanggal) berisi detail (obat, **nomor bets, tanggal kedaluwarsa, jumlah, harga beli**). Setiap detail membuat `apt_stok_batch` + mutasi `penerimaan`. Unik `(supplier_id, no_faktur)`.

### 8.4 Retur
- **Ke supplier**: alasan `rusak | cacat | mendekati_kedaluwarsa | kedaluwarsa | lainnya`; melacak `penerimaan_id` (faktur asal); mengurangi batch (mutasi `retur_supplier`, negatif); tidak boleh melebihi `jumlah_sisa`.
- **Dari pasien**: hanya jika `sys_pengaturan.retur_pasien_diizinkan = 1`; obat belum dibuka/rusak; stok kembali ke **batch asal** (mutasi `retur_pasien`, positif) dan tagihan disesuaikan. Perlu persetujuan (`disetujui_oleh`).

### 8.5 Racikan
- Apoteker memilih bahan dari inventaris (atau dari template `mtr_racikan`); data bahan di `erm_resep_racikan_bahan`.
- Saat resep dikonfirmasi **selesai diracik**: untuk SETIAP bahan panggil `StokService::kurangi` dalam SATU transaksi (semua bahan terpotong atau tidak ada yang terpotong). Jenis mutasi `racikan`.
- **Jasa racik = biaya flat** (nilai di `sys_pengaturan.biaya_jasa_racik`), tidak dihitung per jumlah bungkus/kapsul. Disnapshot ke `erm_resep_item.biaya_jasa_racik`. **[USULAN]** satu flat per item racikan; harga obat racikan = Σ(jumlah bahan × harga jual bahan).

### 8.6 Penjualan umum
Tanpa resep, untuk umum. Membuat kunjungan `apotek_umum` (opsional pasien), memotong stok FEFO (mutasi `penjualan_umum`), lalu tagihan.

### 8.7 Stock opname
Header `apt_opname` (`draf → final`), detail per batch: `stok_sistem`, `stok_fisik`, `selisih = fisik − sistem`. Saat **final**: untuk selisih ≠ 0 buat mutasi `opname_plus/opname_minus`, set `jumlah_sisa = stok_fisik`. Setelah final tidak bisa diubah.

## 9. Billing / kasir
- Satu tagihan per kunjungan (`bil_tagihan.kunjungan_id` unik) atau per penjualan umum. Detail per komponen: `konsultasi | tindakan | lab | obat | jasa_racik | lainnya`, dengan `referensi_tipe/id` ke sumbernya (agar pembentukan ulang tidak menggandakan baris — gunakan `updateOrCreate` pada referensi).
- Sumber komponen: konsultasi ← `mtr_dokter.tarif_konsultasi` (snapshot, hanya kunjungan poli); tindakan ← `erm_tindakan`; lab ← `lab_permintaan_detail` (saat dikunci); obat ← item resep/penjualan (harga jual snapshot); jasa racik ← `erm_resep_item.biaya_jasa_racik`. Lab mandiri & apotek umum **tanpa** biaya konsultasi.
- `total = subtotal − diskon`; `subtotal = Σ detail.subtotal`. Semua uang `DECIMAL(15,2)`; hitung dengan `bcmath`/string, bukan float.
- Pembayaran (`bil_pembayaran`): metode dari master, `jumlah_bayar`, `kembalian`, kasir. Tagihan `lunas` ketika Σ pembayaran ≥ total.
- **Konfirmasi lunas → dalam satu transaksi**: tagihan `lunas`, kunjungan `selesai`, `erm_pemeriksaan.dikunci = 1`, `lab_permintaan.dikunci = 1`.
- Pembayaran hanya boleh jika kunjungan berstatus `kasir` (semua unit selesai).

## 10. Invarian yang WAJIB punya tes otomatis
1. Nomor antrean unik per (poli, tanggal) di bawah beban konkuren; reset di hari berikutnya.
2. Per batch: `jumlah_sisa = Σ mutasi`; `jumlah_sisa ≥ 0`.
3. FEFO: batch berkedaluwarsa paling dekat habis lebih dulu; batch kedaluwarsa tidak pernah keluar.
4. Racikan atomik: gagal satu bahan → tidak ada stok yang berubah.
5. Satu tagihan per kunjungan; `total = Σ detail − diskon`.
6. Data terkunci tidak dapat diubah lewat Service, controller, maupun API.
7. Perpindahan status kunjungan hanya yang sah (§1).
8. Izin: peran tanpa hak `tambah` mendapat 403 pada `store`, dst.

## 11. Event SATUSEHAT
- Daftar event domain tetap pada D14; event dipancarkan hanya setelah transaksi commit.
- Sesuai D33, listener SATUSEHAT hanya membuat `sts_sinkronisasi` untuk Encounter, Observation (TTV dan hasil lab), ServiceRequest, Specimen, dan Composition.
- `ResepDiterbitkan` dan `ResepDiserahkan` tetap event domain untuk kebutuhan internal, tetapi tidak memicu sinkronisasi SATUSEHAT selama MedicationRequest/MedicationDispense di luar cakupan.
- `PemeriksaanSelesai` hanya memperbarui Encounter menjadi `finished`; Condition tidak dikirim.
