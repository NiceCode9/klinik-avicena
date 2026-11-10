# Aturan Bisnis — SIM Klinik Avicena

Sumber: `PRD.md` + keputusan di `decisions.md`. Item bertanda **[USULAN]** adalah asumsi saya yang belum dikonfirmasi; lihat "Pertanyaan terbuka" di `decisions.md` sebelum mengimplementasikannya final.

## 1. Status kunjungan (`reg_kunjungan.status`)
| Jenis | Dari | Ke | Syarat |
|---|---|---|---|
| poli | terdaftar | ttv | TTV tersimpan (`reg_ttv`) |
| poli | ttv | menunggu | antrean poli dibuat (`reg_antrean`) |
| poli | menunggu | diperiksa | dokter memanggil/mulai |
| poli | diperiksa | farmasi / lab | pemeriksaan disimpan; tagihan farmasi dibuat |
| poli | lab | farmasi / selesai | semua hasil lab terkunci dan tagihan lab lunas |
| poli | farmasi | lab / selesai | tagihan farmasi lunas; lab masih ada → `lab` |
| poli | (sebelum selesai) | batal | alasan wajib; data medis tidak dihapus |
| lab_mandiri | terdaftar | lab | pendaftaran lab selesai |
| lab_mandiri | lab | selesai | tagihan lab lunas |
| apotek_umum | terdaftar | farmasi | penjualan dibuat |
| apotek_umum | farmasi | selesai | tagihan lunas |
| semua | (sebelum selesai) | batal | alasan wajib |

- Status `kasir` tidak dipakai lagi (D42). Apotek dan lab memegang pembayaran masing-masing.
- `StatusKunjunganService::sinkron()` menghitung ulang status setelah tiap unit selesai. Untuk `poli`: bila masih ada lab yang belum terkunci/belum lunas → `lab`; selain itu bila tagihan farmasi ada dan belum lunas → `farmasi`; selain itu `selesai`. Untuk `lab_mandiri`: `selesai` setelah tagihan lab lunas. Untuk `apotek_umum`: `selesai` setelah tagihan farmasi lunas.
- Tagihan farmasi selalu dibuat untuk pasien poli setelah dokter menyimpan pemeriksaan, meskipun tidak ada resep (berisi biaya periksa).
- Perpindahan status HANYA lewat Service yang memvalidasi tabel di atas. Dilarang `update(['status' => ...])` langsung dari controller.
- `sinkron()` mengunci `reg_kunjungan`, lalu `lab_permintaan`, `erm_resep`, dan `bil_tagihan` terkait dengan `FOR UPDATE` agar membaca data terbaru (bukan snapshot lama transaksi pemanggil).
- Setiap perpindahan status mencatat baris `reg_kunjungan_riwayat_status` (status, waktu, pengguna). `waktu_datang` terisi saat dibuat, `waktu_mulai` saat `diperiksa`, `waktu_selesai` saat `selesai`.

## 2. Pasien & NIK
- Pencarian pasien oleh NIK memakai `nik_hash = HMAC-SHA256(nik, NIK_HMAC_KEY)`. `nik` disimpan terenkripsi (cast `encrypted`).
- NIK harus 16 digit angka; satu NIK = satu pasien (unique `nik_hash`). Jika NIK belum ada, perawat/petugas mendaftarkan pasien baru (data rekam medis dasar).
- `no_rekam_medis` unik, dibuat otomatis oleh `PendaftaranService` **[format: lihat pertanyaan terbuka]**.
- Peran `petugas_lab` boleh menambah pasien baru tetapi tidak mengubah identitas pasien yang sudah ada.
- Saat mendaftar kunjungan poli, petugas memilih poli dan dokter; `erm_pemeriksaan` kosong dibuat otomatis dengan dokter tersebut (D41). `PemeriksaanService::mulai` memakai baris itu, bukan membuat baris baru.

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
- Diisi perawat di loket pendaftaran, sebelum nomor antrean diterbitkan. Wajib untuk kunjungan poli: tekanan darah (sistolik/diastolik), nadi, frekuensi napas, suhu, berat badan, tinggi badan, saturasi oksigen, gula darah, dan skala nyeri (0-10). Satu TTV per kunjungan.
- Dokter dapat memperbarui TTV yang sama saat pemeriksaan; perubahan tercatat di `sys_log_aktivitas`.
- Lab mandiri: TTV opsional (Q7).

## 5. Rekam medis (ERM)
- Satu form ERM untuk poli penyakit dalam, dibuat kosong saat pendaftaran (D41). Form ini menggantikan form khusus penyakit dalam, odontogram, dan detail poli lain; tabel lama dibiarkan tidak dipakai.
- Header: identitas pasien, No. RM, tanggal lahir/umur, judul unit = nama poli. Tombol Riwayat Lab dan Riwayat Radiologi mengambil data lintas kunjungan pasien.
- Isi: `subjektif` (textarea Subjektif dan Objektif), TTV, `riwayat_kesehatan`, `objektif` (Pemeriksaan Fisik), diagnosis ICD-10, planning (lab, radiologi, e-resep, catatan di `rencana`), cara pulang (`pulang`, `dirujuk`, `kontrol_lagi` — disimpan di `reg_kunjungan.kode_cara_keluar`/`nama_cara_keluar` yang sudah ada), dan tanda tangan dokter pada cetakan.
- Diagnosis memakai pilihan ICD-10 (select2), bukan teks bebas: tepat satu `primer`, jumlah `sekunder` tidak dibatasi.
- Dokter hanya melihat/mengubah pemeriksaan yang ia buat sampai terkunci (Policy). Dokter lain boleh MELIHAT riwayat pasien (hak `lihat`).
- `erm_pemeriksaan.dikunci` diisi saat tagihan farmasi lunas. Pemeriksaan terkunci tidak bisa diubah/dihapus. Koreksi setelah terkunci: belum diputuskan (Q6).
- “Diagnosa Tindakan” tidak dipakai karena tindakan medis di luar cakupan.
- Perubahan data medis memancarkan event domain `afterCommit`; listener SATUSEHAT membuat `sts_sinkronisasi` + job (lihat `satusehat.md`).

## 6. e-Resep
- Status: `diterbitkan → divalidasi → diracik (jika ada racikan) → diserahkan | batal`.
- Item `jenis = reguler` wajib `obat_id`; item `racikan` wajib punya bahan di `erm_resep_racikan_bahan` (bisa disalin dari template `mtr_racikan`).
- Validasi apoteker memeriksa ketersediaan stok (hanya batch belum kedaluwarsa).
- Item wajib memuat dosis terstruktur (`dosis`, `satuan_dosis`, `frekuensi`, `periode`, `satuan_periode`, `kode_rute`, `lama_hari`); `aturan_pakai` teks tetap disimpan untuk tampilan.
- Harga dan subtotal TIDAK ditampilkan saat dokter meresepkan. `harga_satuan` dan `biaya_jasa_racik` diisi apoteker saat penyerahan (D36), dari harga batch dan pengaturan saat itu.
- Copy riwayat e-resep menyalin item dari resep kunjungan sebelumnya ke resep baru; paket obat ditunda.
- KFA tidak menjadi prasyarat SATUSEHAT (D33); obat tanpa KFA tetap bisa diresepkan dan diserahkan.

## 7. Laboratorium
- **Rujukan**: `erm_rujukan_lab` → otomatis membuat `lab_permintaan` (`sumber = rujukan`) + antrean `LAB-xxx`.
- **Mandiri**: petugas lab mendaftarkan pasien lewat layanan pendaftaran bersama (cari NIK atau daftar baru) → kunjungan `lab_mandiri` → antrean `LAB-xxx` → `lab_permintaan` (`sumber = mandiri`).
- **Dokter lab (`lab_permintaan.dokter_id`)**: dipilih petugas lab dari daftar semua dokter aktif (dokter berpoli LAB diurutkan paling atas). Mandiri: wajib saat pendaftaran. Rujukan: wajib sebelum hasil dikunci. Dokter perujuk berbeda dan tetap di `erm_rujukan_lab.dokter_id`.
- **Kategori**: `mtr_pemeriksaan_lab.kategori` = `lab` atau `radiologi`. Laporan keuangan memisahkan keduanya.
- Hasil per parameter (`lab_hasil`) menyimpan snapshot satuan & nilai rujukan, penanda otomatis (normal/rendah/tinggi) dari batas min/max.
- Kunci hasil (`dikunci = 1`) dilakukan saat verifikasi petugas/dokter lab, SEBELUM pembayaran (D40). Setelah dikunci tidak bisa diubah.
- Tagihan lab dibuat setelah semua hasil terkunci, dengan `bil_tagihan.jenis = lab`; satu tagihan lab per kunjungan. Pasien membayar di kasir lab, bukan di apotek. Kunjungan `selesai` setelah tagihan lab lunas.
- Pengambilan spesimen dicatat di `lab_spesimen` (jenis dari `mtr_pemeriksaan_lab.kode_spesimen`, waktu, petugas) sebelum hasil diinput; `lab_permintaan_detail.spesimen_id` terisi. Permintaan dipecah menjadi **satu baris detail per kode pemeriksaan**.
- Status spesimen: `menunggu_diambil → diambil → diterima | ditolak`. Hasil hanya boleh diinput setelah status `diterima`; `ditolak` wajib mengisi `catatan`.

## 8. Apotek & inventaris

### 8.1 Model stok
- Stok hanya ada di **apotek**; tidak ada gudang, permintaan, atau distribusi antar-lokasi (D34).
- Stok disimpan **per batch** (`apt_stok_batch`: obat + nomor bets + tanggal kedaluwarsa + harga beli + `harga_jual` + `jumlah_sisa`).
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
Satu faktur (`apt_penerimaan`: no faktur, supplier, tanggal, `ppn_persen` nullable) berisi detail (obat, **nomor bets, tanggal kedaluwarsa, jumlah, harga beli/HNA**). Setiap detail membuat `apt_stok_batch` + mutasi `penerimaan`. Unik `(supplier_id, no_faktur)`.

Harga jual batch dihitung saat penerimaan: `harga_jual = (HNA + PPN) × (1 + margin_golongan)`. `ppn_persen` kosong berarti HNA sudah termasuk PPN. Margin diambil dari `sys_pengaturan` sesuai `mtr_obat.golongan`. Hasil disimpan apa adanya (tanpa pembulatan) dan tidak berubah setelah batch dibuat.

### 8.4 Retur
- **Ke supplier**: alasan `rusak | cacat | mendekati_kedaluwarsa | kedaluwarsa | lainnya`; melacak `penerimaan_id` (faktur asal); mengurangi batch (mutasi `retur_supplier`, negatif); tidak boleh melebihi `jumlah_sisa`.
- **Dari pasien**: hanya jika `sys_pengaturan.retur_pasien_diizinkan = 1`; obat belum dibuka/rusak; stok kembali ke **batch asal** (mutasi `retur_pasien`, positif) dan tagihan disesuaikan. Perlu persetujuan (`disetujui_oleh`).

### 8.5 Racikan
- Apoteker memilih bahan dari inventaris (atau dari template `mtr_racikan`); data bahan di `erm_resep_racikan_bahan`.
- Saat resep dikonfirmasi **selesai diracik**: untuk SETIAP bahan panggil `StokService::kurangi` dalam SATU transaksi (semua bahan terpotong atau tidak ada yang terpotong). Jenis mutasi `racikan`. Harga jual tiap batch yang terpakai disnapshot ke `apt_pengeluaran_resep.harga_jual_satuan`.
- **Jasa racik = biaya flat per item racikan** dari `sys_pengaturan.biaya_jasa_racik`, disnapshot ke `erm_resep_item.biaya_jasa_racik` (tidak dikalikan jumlah bungkus/kapsul).
- Harga obat racikan = Σ(jumlah bahan × harga jual batch yang terpakai). Item racikan **tidak** dikenakan embalase.
- Item reguler dikenakan embalase per item dari `sys_pengaturan.embalase`, dan harga obat = Σ(jumlah × harga jual batch).

### 8.6 Penjualan umum
Tanpa resep, untuk umum. Membuat kunjungan `apotek_umum` (opsional pasien) atau penjualan tanpa kunjungan, memotong stok FEFO (mutasi `penjualan_umum`), lalu tagihan `jenis = farmasi`. Pembeli umum hanya membayar harga jual obat; tanpa biaya periksa, embalase, jasa racik, uang kopi, atau simpanan.

### 8.7 Stock opname
Header `apt_opname` (`draf → final`), detail per batch: `stok_sistem`, `stok_fisik`, `selisih = fisik − sistem`. Saat **final**: untuk selisih ≠ 0 buat mutasi `opname_plus/opname_minus`, set `jumlah_sisa = stok_fisik`. Setelah final tidak bisa diubah.

### 8.8 Pengaturan apotek
- `sys_pengaturan` menyimpan `embalase`, `uang_kopi`, `simpanan_default`, dan `margin_bebas`, `margin_bebas_terbatas`, `margin_keras`, `margin_narkotika`, `margin_psikotropika`.
- `biaya_jasa_racik` yang sudah ada dipakai untuk jasa racik.
- Perubahan pengaturan hanya berlaku untuk transaksi berikutnya; harga batch dan baris tagihan yang sudah ada tidak berubah.

## 9. Billing / kasir

### 9.1 Dua tagihan per kunjungan
- `bil_tagihan.jenis = farmasi` untuk pasien poli (dan penjualan umum) dan `jenis = lab` untuk lab. Unik `(kunjungan_id, jenis)`.
- Tagihan farmasi pasien poli berisi: biaya periksa (`konsultasi`), obat, embalase, jasa racik, uang kopi, simpanan.
- Tagihan lab berisi tarif pemeriksaan dari `lab_permintaan_detail` saat hasil dikunci.
- Penjualan umum memakai tagihan `farmasi` dengan komponen `obat` saja.
- Detail memakai `referensi_tipe/id` agar pembentukan ulang tidak menggandakan baris (`updateOrCreate`).

### 9.2 Komponen tagihan farmasi
- `konsultasi` ← `erm_pemeriksaan.tarif_konsultasi` (snapshot; default `mtr_dokter.tarif_konsultasi`, dokter boleh mengubah untuk kunjungan itu). Selalu ada untuk pasien poli, termasuk tanpa resep.
- `obat` ← item resep/penjualan dengan harga jual batch saat penyerahan.
- `embalase` ← `sys_pengaturan.embalase` × jumlah item reguler.
- `jasa_racik` ← `sys_pengaturan.biaya_jasa_racik` × jumlah item racikan.
- `uang_kopi` ← `sys_pengaturan.uang_kopi`, sekali per transaksi.
- `simpanan` ← nilai yang diisi kasir, default `sys_pengaturan.simpanan_default`, sekali per transaksi.
- Uang kopi dan simpanan masuk pemasukan apotek, bukan pendapatan dokter.

### 9.3 Pembayaran
- `subtotal = Σ detail.subtotal`; `total = subtotal − diskon`. Diskon tidak ditampilkan di UI (D43) tetapi kolomnya tetap ada.
- Tunai atau transfer. Transfer wajib `nama_bank` dan `nama_pengirim`; `no_referensi` = ID transfer opsional.
- Pembayaran hanya boleh saat kasir punya shift terbuka (D38). Setiap pembayaran menyimpan `shift_id` dan `kasir_id`.
- Tagihan `lunas` ketika Σ pembayaran ≥ total. Pembayaran pada tagihan lunas ditolak.

### 9.4 Penguncian dan status
- Tagihan farmasi lunas → `erm_pemeriksaan.dikunci = 1` dan `dikunci_pada` terisi, lalu `StatusKunjunganService::sinkron`.
- Hasil lab sudah terkunci saat verifikasi (D40); pembayaran lab tidak mengubah data medis.
- Kunjungan `selesai` bila tagihan farmasi lunas (atau tidak ada) dan tagihan lab lunas (atau tidak ada).

### 9.5 Shift dan setoran dokter
- `bil_shift` dibuka/ditutup manual per akun. Satu akun maksimal satu shift terbuka (ditegakkan Service + lock).
- Shift yang lupa ditutup tidak memblokir kasir lain; muncul di dasbor dan dapat ditutup paksa admin dengan alasan.
- Setoran dokter dicatat di `bil_setoran_dokter` per dokter per shift. Jumlahnya = Σ detail `konsultasi` pada tagihan farmasi yang lunas di shift itu, apa pun metode bayar pasien.
- Setoran punya metode `tunai`/`transfer`, referensi bila transfer, dan rincian porsi tunai/transfer. Default tunai.

## 10. Invarian yang WAJIB punya tes otomatis
1. Nomor antrean unik per (poli, tanggal) di bawah beban konkuren; reset di hari berikutnya.
2. Per batch: `jumlah_sisa = Σ mutasi`; `jumlah_sisa ≥ 0`.
3. FEFO: batch berkedaluwarsa paling dekat habis lebih dulu; batch kedaluwarsa tidak pernah keluar.
4. Racikan atomik: gagal satu bahan → tidak ada stok yang berubah.
5. Satu kunjungan punya maksimal satu tagihan farmasi dan satu tagihan lab; `total = Σ detail − diskon`.
6. Harga batch = `(HNA + PPN) × (1 + margin golongan)`; harga tidak berubah setelah batch dibuat.
7. Embalase hanya untuk item reguler; uang kopi dan simpanan tepat satu baris per tagihan farmasi.
8. Data terkunci tidak dapat diubah lewat Service, controller, maupun API.
9. Perpindahan status kunjungan hanya yang sah (§1); `selesai` menunggu kedua tagihan lunas.
10. Maksimal satu shift terbuka per akun; pembayaran ditolak tanpa shift terbuka.
11. Setoran dokter = Σ `konsultasi` tagihan farmasi lunas pada shift itu; tidak dipengaruhi metode bayar pasien.
12. Izin: peran tanpa hak `tambah` mendapat 403 pada `store`, dst.

## 11. Event SATUSEHAT
- Daftar event domain tetap pada D14; event dipancarkan hanya setelah transaksi commit.
- Sesuai D33, listener SATUSEHAT hanya membuat `sts_sinkronisasi` untuk Encounter, Observation (TTV dan hasil lab), ServiceRequest, Specimen, dan Composition.
- `ResepDiterbitkan` dan `ResepDiserahkan` tetap event domain untuk kebutuhan internal, tetapi tidak memicu sinkronisasi SATUSEHAT selama MedicationRequest/MedicationDispense di luar cakupan.
- `PemeriksaanSelesai` hanya memperbarui Encounter menjadi `finished`; Condition tidak dikirim.
