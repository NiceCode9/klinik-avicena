# Kumpulan Prompt per Fase — SIM Klinik Avicena

Simpan sebagai `docs/prompts.md`. Fase 0 dan 1 sudah selesai, jadi dokumen ini mulai dari Fase 2.

## Cara memakai
1. **Awal setiap sesi AI baru**: tempel blok **PEMBUKA** (di bawah) sekali. Ia berisi protokol kerja dan penutup yang berlaku untuk semua tugas dalam sesi itu.
2. Tempel **satu prompt tugas** per giliran, berurutan (2.1 → 2.2 → …). Jangan menggabungkan tugas.
3. Setelah semua tugas satu fase selesai, jalankan **P-AUDIT** untuk fase itu.
4. Jika AI melenceng, pakai **P-KOREKSI**. Jika sesi baru/konteks hilang, pakai **P-LANJUT**.
5. Setiap prompt tugas menyebut dokumen dan bagian yang relevan. Jangan dihapus: itulah yang menjaga AI tetap pada aturan.

Catatan: sebelum Fase 3, tambahkan keputusan ini ke `docs/decisions.md` (D14) karena prompt 3.1 dan 7.3 bergantung padanya:
`D14 | Penyimpanan data medis memancarkan event PemeriksaanDisimpan (afterCommit). Listener SATUSEHAT (Fase 7) yang membuat baris sts_sinkronisasi dan mendispatch SatuSehatSyncJob. | Memisahkan ERM dari integrasi, ERM bisa selesai tanpa SATUSEHAT.`

---

## PEMBUKA (tempel di awal setiap sesi)

```
Kamu mengembangkan SIM Klinik Avicena (Laravel 13, MySQL 8, AdminLTE 4 via Vite).

SEBELUM melakukan apa pun, baca SELURUH file ini dan jadikan hukum proyek:
AGENTS.md, docs/architecture.md, docs/database.md, docs/business-rules.md,
docs/permissions.md, docs/conventions.md, docs/satusehat.md, docs/decisions.md, docs/roadmap.md.
Jika ada konflik antara instruksiku di chat dan dokumen-dokumen itu, BERHENTI dan tanyakan,
jangan memilih sendiri.

PROTOKOL KERJA (berlaku untuk setiap tugas dalam sesi ini):
1. RENCANA DULU. Sebelum menulis kode, tulis: file yang akan dibuat/diubah, langkah, asumsi,
   dan pertanyaan. Tunggu persetujuanku. Jangan menulis kode sebelum aku menjawab "lanjut".
2. TES DULU untuk logika kritis (antrean, stok, tagihan, kunci data, status, izin):
   tulis tes yang gagal, lalu implementasi sampai lulus.
3. SATU TUGAS = satu cakupan. Jangan mengerjakan hal di luar tugas ("sekalian" dilarang),
   jangan refactor file lain, jangan menambah fitur yang tidak diminta.
4. Jangan menambah package/dependency, jangan mengubah migration yang sudah ada
   (buat migration baru jika skema harus berubah, dan minta persetujuan dulu).
5. Aturan bisnis yang tidak jelas: lihat "Pertanyaan terbuka" di docs/decisions.md.
   Pakai default yang tertulis di sana, tandai di kode dengan komentar TODO(Qn), dan laporkan di akhir.
   Jika aturan tidak ada di dokumen mana pun, TANYA, jangan menebak.
6. Seluruh penamaan Indonesia + prefix modul; created_at/updated_at/deleted_at tetap Inggris.
   Pesan UI dan validasi berbahasa Indonesia.

PENUTUP (wajib di akhir setiap tugas, setelah kode selesai):
a. Jalankan: php artisan test dan vendor/bin/pint; tampilkan ringkas hasilnya.
b. Tampilkan CHECKLIST KEPATUHAN: untuk tiap 12 aturan "ATURAN WAJIB" di AGENTS.md tulis
   ✓ (patuh) / ✗ (melanggar, jelaskan) / – (tidak relevan), dan centang Definition of Done.
c. Daftar: file yang dibuat/diubah, asumsi yang diambil, TODO(Qn) yang tersisa, dan
   hal yang perlu kuperiksa manual di browser.
d. Usulkan perubahan docs/roadmap.md (centang) dan docs/decisions.md bila ada keputusan baru.
Jangan menyatakan "selesai" jika ada tes gagal atau satu pun ✗.

Konfirmasi bahwa kamu sudah membaca semua dokumen dengan meringkas (maks. 10 baris)
aturan paling penting yang berlaku, lalu tunggu tugas pertamaku.
```

---

## Prompt pendukung

### P-AUDIT (setelah setiap fase selesai; ganti `<FASE N>`)
```
Protokol sesi (PEMBUKA) berlaku. Tugas: AUDIT KEPATUHAN untuk <FASE N>. JANGAN mengubah file apa pun.
1. Periksa seluruh kode fase ini terhadap AGENTS.md (12 aturan wajib + daftar JANGAN),
   docs/business-rules.md (bagian terkait), docs/permissions.md, docs/conventions.md.
2. Cari pelanggaran spesifik: logika bisnis di controller/Blade/model; update stok/status langsung
   tanpa Service; route/controller tanpa middleware izin; menu tidak terdaftar di config/menu.php;
   float untuk uang; N+1 query; NIK/hasil lab/SOAP di log; data terkunci bisa diubah;
   transaksi DB yang hilang; package baru; migration lama diubah; penamaan tidak Indonesia.
3. Jalankan php artisan test dan vendor/bin/pint; laporkan cakupan tes untuk invarian di
   business-rules §10 yang relevan.
4. Keluaran: tabel temuan (file:baris, aturan yang dilanggar, tingkat: kritis/sedang/ringan,
   saran perbaikan), lalu daftar perbaikan berurutan. Tandai mana yang kritis.
5. Usulkan pembaruan docs/roadmap.md untuk fase ini.
```

### P-KOREKSI (saat AI menyimpang)
```
STOP. Kamu melanggar aturan proyek: <sebutkan, mis. "memotong stok langsung di controller" / "menambah package X" / "mengubah migration lama">.
Rujukan: AGENTS.md aturan #<n> / docs/<file> bagian <x>.
Jangan melanjutkan tugas. Lakukan: (1) jelaskan kenapa itu melanggar, (2) tunjukkan semua tempat
lain yang punya pelanggaran serupa, (3) usulkan perbaikan minimal yang sesuai dokumen,
(4) tunggu persetujuanku sebelum mengubah apa pun.
```

### P-LANJUT (sesi baru / konteks hilang)
```
Protokol sesi (PEMBUKA) berlaku. Kita sedang di <FASE N>, tugas terakhir selesai: <ID prompt, mis. 2.3>.
Baca docs/roadmap.md dan git log -n 20 untuk memahami kondisi sekarang. Jangan menulis kode.
Ringkas: apa yang sudah ada, apa yang belum, dan konfirmasi tugas berikutnya = <ID prompt>.
Periksa bahwa tes yang ada lulus (php artisan test) sebelum kita lanjut.
```

### P-DOKUMEN (setelah keputusan/aturan berubah)
```
Protokol sesi (PEMBUKA) berlaku. Perbarui dokumentasi agar sesuai kode terbaru. Jangan ubah kode.
Periksa: docs/roadmap.md (status), docs/decisions.md (keputusan baru, pertanyaan yang terjawab),
docs/business-rules.md, docs/database.md (jika ada migration baru), docs/permissions.md (menu/hak baru).
Tampilkan diff dokumen, tunggu persetujuanku, baru terapkan.
```

### P-BUG (perbaikan bug)
```
Protokol sesi (PEMBUKA) berlaku. Bug: <deskripsi + langkah reproduksi + pesan error>.
1) Tulis tes yang mereproduksi bug dan gagal. 2) Cari akar masalah (jelaskan, jangan menebak).
3) Perbaiki seminimal mungkin di lapisan yang benar (Service, bukan controller). 4) Tes lulus.
Jangan mengubah perilaku lain. Jika perbaikan menyentuh aturan di docs/, laporkan.
```

---

# FASE 2 — Pendaftaran & Antrean
Dokumen utama: `business-rules.md` §1–§4, `conventions.md` (pola AntreanService), `permissions.md`.

### 2.1 PendaftaranService (pasien)
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: buat App\Services\Registrasi\PendaftaranService dan NikHasher.
Dokumen: business-rules §2, database.md (mtr_pasien), decisions D4 & Q8.
Cakupan:
- NikHasher::hash(string $nik): HMAC-SHA256 memakai config('app.nik_hmac_key') dari env NIK_HMAC_KEY
  (tambahkan ke config + .env.example; gagal keras jika kunci kosong).
- cariByNik(string $nik): ?Pasien lewat nik_hash (NIK harus 16 digit angka).
- daftarkanPasienBaru(array $data, Pengguna $oleh): Pasien — nik terenkripsi (cast), nik_hash terisi,
  no_rekam_medis dibuat atomik sesuai default Q8 (RM-000001 berurut). Usulkan mekanisme penomoran atomik
  (mis. baris sys_pengaturan + lockForUpdate, atau tabel penghitung via migration BARU) dan MINTA
  PERSETUJUAN sebelum membuat tabel baru.
- Exception domain: NikSudahTerdaftarException, NikTidakValidException.
- Catat ke sys_log_aktivitas.
Tes: NIK valid/tidak valid, NIK ganda ditolak, hash konsisten, nomor RM berurut dan unik,
nik tersimpan terenkripsi (nilai mentah di DB != NIK asli).
Selesai bila: tes lulus; tidak ada query berdasarkan kolom nik (hanya nik_hash).
```

### 2.2 AntreanService
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: buat App\Services\Registrasi\AntreanService dengan ambilNomor(Poli, Kunjungan): Antrean,
plus panggil(), layani(), selesai(), batal() untuk perubahan status antrean.
Dokumen: business-rules §3 dan invarian §10 no.1; conventions.md (contoh ambilNomor); decisions D6.
Aturan: transaksi + lock pada reg_penghitung_antrean; format KODE-001 (INT/GIG/SRF/LAB), reset per tanggal;
status antrean menunggu→dipanggil→dilayani→selesai|batal dengan validasi transisi (exception jika tidak sah).
Tes WAJIB: nomor berurutan; hari berikutnya mulai dari 1; poli berbeda punya urutan terpisah;
unique index (poli_id, tanggal, nomor_urut) mencegah duplikat; uji konkurensi (dua koneksi/proses
paralel bila memungkinkan, jelaskan metode tesmu); transisi status tidak sah ditolak.
Selesai bila: tes lulus dan tidak ada kode lain yang membuat Antrean tanpa lewat service ini.
```

### 2.3 KunjunganService & StatusKunjunganService
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: buat KunjunganService (buatKunjunganPoli, simpanTtv) dan StatusKunjunganService
(pindahkan(Kunjungan, status) berbasis tabel transisi, dan sinkron(Kunjungan) untuk status kasir).
Dokumen: business-rules §1 (ikuti tabel transisi APA ADANYA), §4, decisions Q5, Q7, Q8.
Aturan:
- no_kunjungan format default Q8 (KJ-YYYYMMDD-0001), atomik.
- simpanTtv: validasi sistolik/diastolik/nadi/suhu/berat badan terisi, satu TTV per kunjungan,
  status terdaftar→ttv; lalu AntreanService::ambilNomor dan status ttv→menunggu.
- Semua perpindahan status HANYA lewat StatusKunjunganService (tolak transisi tidak sah dengan
  StatusTidakValidException). Tidak boleh ada update status langsung di tempat lain.
- sinkron(): implementasikan sesuai usulan paralel lab+farmasi di §1 (kasir hanya jika semua
  lab_permintaan terkunci DAN semua resep diserahkan/batal); karena Fase 3-5 belum ada, buat
  dengan data kosong lulus dan beri tes untuk skenario-skenario itu memakai factory.
Tes: seluruh matriks transisi sah/tidak sah; TTV wajib; sinkron() berbagai kombinasi.
Selesai bila: tes lulus; grep tidak menemukan "->status =" / update status kunjungan di luar service ini.
```

### 2.4 UI Data Pasien
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: halaman Data Pasien (menu pendaftaran.pasien, route pasien.*), memakai PendaftaranService.
Dokumen: permissions.md (pola controller + hak awal), business-rules §2, conventions.md.
Cakupan: index dengan pencarian (nama, no RM, NIK→lewat nik_hash) + pagination server-side;
create/store; edit/update; show (identitas; NIK ditampilkan sebagian, mis. 3 digit awal + 3 akhir,
penuh hanya untuk peran yang berhak); FormRequest dengan pesan Indonesia.
Policy: PasienPolicy — petugas_lab boleh create tetapi TIDAK boleh update (sesuai permissions.md).
Middleware izin pada semua action; tombol dibungkus @izin. Ikuti gaya halaman CRUD master yang sudah ada.
Tes: 200/403 per peran (resepsionis, perawat, petugas_lab, dokter), pencarian NIK tidak memakai kolom nik,
petugas_lab ditolak saat update.
Selesai bila: DoD di AGENTS.md terpenuhi.
```

### 2.5 UI Pendaftaran Kunjungan, TTV, Cetak Antrean
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: alur UI pendaftaran kunjungan poli: menu pendaftaran.kunjungan dan pendaftaran.ttv.
Dokumen: business-rules §1, §3, §4; permissions.md; conventions.md.
Alur: cari pasien lewat NIK (tidak ketemu → arahkan ke form pasien baru) → pilih poli (INT/GIG/SRF) →
buat kunjungan → halaman input TTV oleh perawat → setelah TTV tersimpan nomor antrean terbit →
halaman cetak nomor antrean (CSS print lebar 80mm: nama klinik, nomor besar, poli, tanggal-jam).
Logika hanya lewat service Fase 2.1–2.3. Daftar kunjungan hari ini dengan filter poli/status.
Hak: sesuai HakAksesAwalSeeder (resepsionis, perawat).
Tes: alur lengkap sampai nomor antrean; TTV tidak lengkap ditolak; resepsionis tanpa hak TTV mendapat 403.
Selesai bila: dari pasien baru sampai cetak nomor antrean berjalan di browser; nomor sesuai format PRD.
```

### 2.6 Daftar & Panggil Antrean
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: halaman antrean hari ini per poli (route kunjungan/antrean sesuai konvensi; pakai menu yang sudah ada
atau usulkan menu baru di config/menu.php dan minta persetujuan).
Dokumen: business-rules §3, architecture.md §10 (polling, bukan websocket).
Cakupan: tabel antrean per poli berurut nomor; tombol panggil/layani/selesai/batal lewat AntreanService;
endpoint JSON ringan untuk polling tiap 5–10 detik (tanpa data pribadi berlebihan: nomor, status, nama
inisial); badge jumlah menunggu.
Catatan: perpindahan kunjungan menunggu→diperiksa terjadi di Fase 3 saat dokter memulai pemeriksaan;
jangan dikerjakan di sini.
Tes: izin, transisi status antrean, endpoint JSON hanya memuat field yang disetujui.
```

### 2.7 (Opsional) Layar TV Antrean
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: layar publik read-only menampilkan nomor yang sedang dipanggil per poli (INT/GIG/SRF/LAB).
Aturan: halaman tanpa login hanya boleh menampilkan NOMOR ANTREAN dan poli — tidak ada nama/NIK.
Lindungi dengan token layar di config (bukan data pasien); polling endpoint JSON. Tes: tidak ada field pribadi.
Ini opsional; jika menurutmu melanggar AGENTS.md aturan #6/#11, jelaskan dan tanya dulu.
```

### 2.8 P-AUDIT Fase 2 (pakai P-AUDIT dengan `<FASE N>` = Fase 2)

---

# FASE 3 — Rekam Medis & e-Resep
Dokumen utama: `business-rules.md` §5–§7, `permissions.md` (Policy), `database.md` (tabel erm_*).

### 3.1 PemeriksaanService (SOAP + diagnosis)
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: App\Services\Erm\PemeriksaanService: mulai(Antrean|Kunjungan, Dokter), simpanDraf(), simpanFinal().
Dokumen: business-rules §1 & §5, decisions D14 (event PemeriksaanDisimpan) & D13.
Aturan:
- mulai(): status kunjungan menunggu→diperiksa (via StatusKunjunganService), antrean dilayani (AntreanService).
- simpanDraf/Final: SOAP + diagnosis ICD-10 (final wajib minimal 1 diagnosis primer); transaksi DB.
- Setelah commit simpanFinal: pancarkan event App\Events\PemeriksaanDisimpan dengan afterCommit (tanpa
  listener SATUSEHAT di fase ini; JANGAN memanggil API atau membuat job SATUSEHAT sekarang).
- Setelah final dan tanpa lab/resep → status kunjungan ke kasir (sinkron()).
- Pemeriksaan terkunci (dikunci=1) tidak bisa diubah: DataTerkunciException.
- Catat sys_log_aktivitas.
Tes: mulai→draf→final; final tanpa diagnosis primer ditolak; event terpancar setelah commit dan tidak
terpancar jika transaksi gagal; terkunci menolak ubah; transisi status benar.
```

### 3.2 UI Pemeriksaan (SOAP + ICD-10)
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: halaman pemeriksaan dokter (menu erm.pemeriksaan): daftar antrean poli dokter, mulai pemeriksaan,
form SOAP, autocomplete ICD-10 (endpoint JSON berizin erm.pemeriksaan; query terindeks, maksimal 20 hasil).
Dokumen: business-rules §5, permissions.md (PemeriksaanPolicy), conventions.md.
Tampilkan ringkasan TTV pasien dan alergi/riwayat singkat yang tersedia di data (jangan membuat kolom baru).
PemeriksaanPolicy: ubah hanya oleh dokter pembuat saat dikunci=0; dokter lain hanya melihat.
Tes: 403 untuk non-dokter, dokter lain tidak bisa mengubah, autocomplete ICD-10 membatasi hasil.
```

### 3.3 Form Khusus Penyakit Dalam & Saraf
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: form khusus poli (erm_detail_penyakit_dalam, erm_detail_saraf).
Dokumen: PRD §3.2, business-rules §5, database.md.
Langkah 1 (tanpa kode): usulkan struktur JSON untuk review_sistem_organ, evaluasi_motorik,
evaluasi_sensorik, saraf_kranial (N I–XII) beserta tampilan form; tunggu persetujuanku.
Langkah 2: implementasi lewat PemeriksaanService (bukan controller), validasi struktur JSON, tampilan
terpisah per poli (Penyakit Dalam: riwayat penyakit kronis, status metabolik, review sistem organ;
Saraf: motorik, sensorik, saraf kranial). Tes: validasi struktur, data tersimpan/terbaca ulang, terkunci menolak.
```

### 3.4 Odontogram Digital (Poli Gigi)
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: odontogram digital → tabel erm_odontogram (notasi FDI, permukaan, kondisi, tindakan, catatan).
Dokumen: PRD §3.2, business-rules §5, AGENTS.md (jangan tambah package tanpa izin).
Langkah 1: usulkan desain komponen (SVG + JavaScript vanilla/modul Vite, gigi dewasa 11–48 dan susu
51–85, klik gigi → pilih permukaan & kondisi) tanpa library baru; tunggu persetujuanku.
Langkah 2: implementasi: komponen Blade + JS kecil, endpoint simpan lewat PemeriksaanService
(replace-all atau diff per gigi, jelaskan pilihanmu), tampilan riwayat odontogram kunjungan sebelumnya.
Tes (backend): nomor gigi FDI valid saja, permukaan valid, terkunci menolak, hanya poli GIG yang boleh.
```

### 3.5 Tindakan Medis
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: input tindakan medis pada pemeriksaan (erm_tindakan) dari mtr_tindakan.
Dokumen: business-rules §5 & §9, decisions D10 (snapshot tarif).
Aturan: tarif DISNAPSHOT dari mtr_tindakan saat input; tindakan difilter per poli; jumlah > 0;
ubah master tarif tidak mengubah tindakan lama. Lewat PemeriksaanService.
Tes: snapshot tarif, ubah master tidak mempengaruhi, terkunci menolak.
```

### 3.6 e-Resep (reguler + racikan)
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: App\Services\Erm\ResepService dan UI e-Resep (menu erm.resep).
Dokumen: business-rules §6 & §8.5, database.md (erm_resep*, mtr_racikan*), decisions Q2, Q3.
Aturan:
- buat resep dari pemeriksaan: no_resep sesuai Q8, status diterbitkan.
- item reguler: obat_id wajib, jumlah > 0, aturan_pakai; harga_satuan DISNAPSHOT dari mtr_obat.harga_jual.
- item racikan: minimal 1 bahan di erm_resep_racikan_bahan; bisa disalin dari template mtr_racikan
  (salin, bukan referensi hidup); JANGAN memotong stok di fase ini.
- tampilkan stok tersedia sebagai informasi (hanya baca, lewat query ringan; BELUM StokService).
- resep tidak bisa diubah setelah divalidasi apoteker.
Tes: validasi item reguler/racikan, snapshot harga, salin template, perubahan template tidak mengubah resep lama.
```

### 3.7 Rujukan Lab
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: App\Services\Erm\RujukanLabService::buat(Pemeriksaan, array pemeriksaanLabIds, ?string catatan).
Dokumen: business-rules §7 (rujukan), §1, decisions D8.
Aturan (satu transaksi): buat erm_rujukan_lab + detail; otomatis buat lab_permintaan (sumber=rujukan,
rujukan_lab_id, dokter_id NULL) + lab_permintaan_detail (tarif DISNAPSHOT); buat antrean LAB-xxx lewat
AntreanService pada kunjungan yang sama; status kunjungan diperiksa→lab lewat StatusKunjunganService.
UI: pilih pemeriksaan lab di halaman pemeriksaan (menu erm.rujukan_lab).
Tes: semua efek di atas, atomik (gagal sebagian → tidak ada yang tersimpan), nomor LAB-001 dst. benar.
```

### 3.8 Riwayat Kunjungan Pasien
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: halaman riwayat kunjungan pasien untuk dokter (read-only): daftar kunjungan, detail SOAP,
diagnosis, tindakan, resep, hasil lab (jika sudah ada).
Dokumen: permissions.md (dokter boleh melihat riwayat), conventions.md (eager loading + pagination).
Aturan: tidak ada N+1 (buktikan dengan tes yang menghitung jumlah query), NIK ditampilkan terbatas,
akses tercatat di sys_log_aktivitas (aksi "lihat") karena ini data medis sensitif.
```

### 3.9 P-AUDIT Fase 3

---

# FASE 4 — Laboratorium
Dokumen utama: `business-rules.md` §7, `permissions.md`, decision D8.
> Sebelum menjalankan 4.1–4.3, baca "Tambahan untuk 4.1–4.3" di docs/prompts-fase7.md (bagian A) dan tempel bersama prompt.

### 4.1 LabService
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: App\Services\Lab\LabService: mulaiProses(), simpanHasil(), kunci(), tentukanPenanda().
Dokumen: business-rules §7, §1, §10 no.6; database.md (lab_*).
Aturan:
- tentukanPenanda(): normal/rendah/tinggi dari nilai_rujukan_min/max (numerik) — bila bukan numerik, penanda null.
- simpanHasil(): snapshot satuan & nilai rujukan dari mtr_parameter_lab; tolak jika dikunci.
- kunci(): semua lab_permintaan_detail punya hasil; untuk sumber=rujukan, dokter_id WAJIB terisi;
  set dikunci & dikunci_pada; antrean selesai; panggil StatusKunjunganService::sinkron.
Tes: penanda berbagai kasus (batas tepat, di luar batas, non-numerik), kunci tanpa hasil lengkap ditolak,
rujukan tanpa dokter ditolak, setelah kunci tidak bisa ubah, sinkron status terpicu.
```

### 4.2 Pendaftaran Lab Mandiri
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: menu pendaftaran.lab_mandiri: pendaftaran pasien langsung ke lab TANPA pemeriksaan dokter.
Dokumen: business-rules §7 (mandiri) & §1, decisions D8, permissions.md (lookup dokter).
Aturan: gunakan PendaftaranService (cari NIK / daftar pasien baru) — DILARANG menduplikasi logika pasien;
buat kunjungan jenis lab_mandiri (terdaftar→lab, tanpa TTV; TTV opsional sesuai Q7), antrean LAB-xxx,
lab_permintaan (sumber=mandiri, rujukan_lab_id NULL), pilih pemeriksaan, snapshot tarif.
Dokter lab WAJIB dipilih saat pendaftaran dari SEMUA dokter aktif, dokter berpoli LAB diurutkan paling
atas; endpoint lookup dokter dilindungi izin:pendaftaran.lab_mandiri,tambah (BUKAN izin master.dokter).
Hak petugas_lab: boleh tambah pasien baru, tidak boleh ubah pasien lama.
Tes: alur lengkap; tanpa dokter ditolak; petugas_lab tidak bisa update pasien; lookup dokter 403 tanpa izin.
```

### 4.3 UI Antrean & Input Hasil Lab
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: menu lab.antrean dan lab.hasil: antrean lab (rujukan+mandiri), pilih/ubah dokter lab (rujukan),
form hasil per parameter dengan penanda otomatis, tombol Kunci Hasil (konfirmasi).
Semua logika lewat LabService. Hasil dokter (menu lab.hasil, hak L) read-only.
Tes: izin per peran, dokter hanya baca, form menolak input saat terkunci, UI tidak bisa mengunci tanpa dokter (rujukan).
```

### 4.4 Cetak Hasil Lab
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: halaman cetak hasil lab (CSS print A4): identitas pasien, dokter lab, tabel parameter-hasil-satuan-rujukan-
penanda, tanggal. Hanya untuk permintaan yang sudah dikunci. Akses tercatat di sys_log_aktivitas.
Tes: belum dikunci → 403/redirect; data cocok; izin.
```

### 4.5 P-AUDIT Fase 4

---

# FASE 5 — Apotek, Farmasi, Inventaris
Dokumen utama: `business-rules.md` §8 (8.1–8.7) dan §10 no.2–4. **Kerjakan service + tes dulu, UI belakangan.**

### 5.1 StokService (inti)
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: App\Services\Apotek\StokService: tambahBatch(), kurangi() (FEFO), kembalikan(), sesuaikan(),
saldo(Obat). Exception: StokTidakCukupException.
Dokumen: business-rules §8.1–8.2, conventions.md (kerangka FEFO), decisions D5, §10 no.2-4.
Aturan: jumlah_sisa hanya berubah lewat service ini; setiap perubahan = satu baris apt_mutasi_stok
(jenis_mutasi, jumlah +/-, saldo_setelah, referensi_tipe/id); kurangi() memakai FEFO
(tanggal_kedaluwarsa asc, id asc) dengan lockForUpdate, lintas batch, melewati batch kedaluwarsa,
atomik (stok kurang → exception, tidak ada perubahan). Hitung desimal dengan bcmath/brick-math, bukan float.
Tes WAJIB: lintas batch; batch kedaluwarsa dilewati; stok kurang → rollback total; invarian
jumlah_sisa = Σ mutasi setelah rangkaian operasi acak; jumlah_sisa tidak pernah negatif; konkurensi
dua pengeluaran bersamaan tidak membuat stok negatif (jelaskan metode tes).
Selesai bila: tes lulus dan grep tidak menemukan update jumlah_sisa di luar service ini.
```

### 5.2 Obat Masuk
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: PenerimaanService + UI Obat Masuk (menu apotek.penerimaan).
Dokumen: business-rules §8.3, database.md (apt_penerimaan*).
Aturan: faktur (no_faktur, supplier, tanggal) + detail (obat, nomor_bets, tanggal_kedaluwarsa, jumlah,
harga_beli) dalam satu transaksi; tiap detail → StokService::tambahBatch (mutasi 'penerimaan');
unik (supplier_id, no_faktur); total dihitung server-side; kedaluwarsa tidak boleh di masa lalu.
Faktur yang sudah dibuat tidak dihapus (koreksi lewat retur/opname).
Tes: transaksi atomik, faktur ganda ditolak, mutasi tercatat, izin apoteker.
```

### 5.3 Kartu Stok, Peringatan, Laporan
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: menu apotek.stok: daftar stok per obat (Σ batch belum kedaluwarsa), detail batch, kartu stok
(riwayat apt_mutasi_stok), filter stok menipis (< stok_minimum) dan mendekati kedaluwarsa
(sys_pengaturan batas_peringatan_kedaluwarsa_hari). Scheduler harian yang menyiapkan peringatan
(tanpa package baru). Eager loading + pagination, tes jumlah query.
Tes: perhitungan stok, filter, batch kedaluwarsa tidak dihitung sebagai tersedia.
```

### 5.4 Validasi & Penyerahan Resep Reguler
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: ResepApotekService + UI Validasi & Racik Resep (menu apotek.resep) untuk item REGULER dulu.
Dokumen: business-rules §6, §8.2; status resep diterbitkan→divalidasi→diserahkan.
Aturan: validasi memeriksa ketersediaan (tanpa mengurangi stok); penyerahan memanggil StokService::kurangi
per item dalam SATU transaksi, mencatat apt_pengeluaran_resep (batch yang terpakai) dan mutasi 'resep';
setelah semua resep kunjungan diserahkan → StatusKunjunganService::sinkron.
Tes: stok kurang → tidak ada item yang terpotong; pengeluaran per batch tercatat; status valid.
```

### 5.5 Racikan (potong stok atomik + jasa racik)
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: RacikanService: konfirmasi "selesai diracik" untuk item racikan.
Dokumen: business-rules §8.5, decisions Q2 & Q3 (pakai default, tandai TODO(Q2)/TODO(Q3)), §10 no.4.
Aturan: dalam SATU transaksi, untuk SETIAP bahan di erm_resep_racikan_bahan panggil StokService::kurangi
(jenis mutasi 'racikan', pengeluaran tercatat per batch); satu bahan kurang → TIDAK ADA bahan yang terpotong;
jasa racik FLAT dari sys_pengaturan.biaya_jasa_racik disnapshot ke erm_resep_item.biaya_jasa_racik
(tidak dikalikan jumlah bungkus/kapsul); status resep→diracik.
Tes: semua bahan terpotong; satu bahan kurang → rollback total; jasa racik flat tidak berubah oleh
jumlah bungkus; perubahan pengaturan tidak mengubah resep lama.
```

### 5.6 Penjualan Umum
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: PenjualanUmumService + UI (menu apotek.penjualan).
Dokumen: business-rules §8.6, §9.
Aturan: membuat kunjungan jenis apotek_umum (pasien opsional / nama_pembeli); potong stok FEFO
(mutasi 'penjualan_umum', batch tercatat di detail); harga_satuan snapshot; total server-side;
status menunggu_bayar → tagihan dibuat di Fase 6 (jangan membuat tagihan di sini).
Tes: stok terpotong FEFO, stok kurang → batal total, total benar, izin apoteker.
```

### 5.7 Retur ke Supplier
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: ReturSupplierService + UI (menu apotek.retur_supplier).
Dokumen: business-rules §8.4, database.md.
Aturan: alasan rusak/cacat/mendekati_kedaluwarsa/kedaluwarsa/lainnya; lacak faktur asal (penerimaan_id);
jumlah ≤ jumlah_sisa batch; pengurangan lewat StokService (mutasi 'retur_supplier'); status draf→dikirim→
diterima_supplier|batal; pembatalan retur mengembalikan stok lewat StokService.
Tes: tidak boleh melebihi sisa, mutasi benar, batch kedaluwarsa BOLEH diretur, izin.
```

### 5.8 Retur dari Pasien
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: ReturPasienService + UI (menu apotek.retur_pasien).
Dokumen: business-rules §8.4, decisions Q4 (default: tidak diizinkan setelah tagihan lunas; TODO(Q4)).
Aturan: hanya jika sys_pengaturan.retur_pasien_diizinkan = 1; obat belum dibuka/rusak (konfirmasi petugas);
stok kembali ke BATCH ASAL lewat StokService::kembalikan (mutasi 'retur_pasien'); butuh disetujui_oleh;
penyesuaian tagihan di Fase 6 (sediakan titik panggil, jangan menulis tagihan di sini).
Tes: pengaturan nonaktif → ditolak; batch asal bertambah; tanpa persetujuan ditolak; lunas → ditolak (default Q4).
```

### 5.9 Stock Opname
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: OpnameService + UI (menu apotek.opname).
Dokumen: business-rules §8.7.
Aturan: draf (stok_sistem diambil saat dibuat) → input stok_fisik → selisih = fisik − sistem →
FINAL: per batch dengan selisih ≠ 0 buat mutasi opname_plus/opname_minus lewat StokService::sesuaikan
dan set jumlah_sisa = stok_fisik; setelah final tidak bisa diubah. Jika stok sistem berubah antara
draf dan final, tampilkan peringatan dan hitung ulang selisih dari saldo terbaru.
Tes: selisih plus/minus, invarian Σ mutasi tetap benar, final immutable, izin.
```

### 5.10 P-AUDIT Fase 5 (tekankan invarian §10 no.2–4 dan grep update jumlah_sisa)

---

# FASE 6 — Billing / Kasir
Dokumen utama: `business-rules.md` §9, §1, §10 no.5–6; decisions D10, D13, Q1, Q4, Q9.

### 6.1 TagihanService
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: App\Services\Billing\TagihanService::susunDariKunjungan(Kunjungan) dan susunDariPenjualan(PenjualanUmum).
Dokumen: business-rules §9; database.md (bil_*).
Aturan: IDEMPOTEN — dipanggil berulang menghasilkan hasil sama (updateOrCreate per referensi_tipe+referensi_id,
hapus baris yang sumbernya hilang hanya selama belum lunas). Komponen: konsultasi (tarif dokter, kunjungan poli saja),
tindakan, lab (lab_permintaan_detail), obat reguler (harga snapshot × jumlah), obat racikan Σ(bahan × harga jual)
[TODO(Q3)], jasa_racik (snapshot). Lab mandiri & apotek umum tanpa konsultasi. Satu bil_tagihan per kunjungan
(unik). subtotal = Σ detail; total = subtotal − diskon. Uang pakai bcmath/brick-math, bukan float.
Tagihan lunas tidak bisa disusun ulang (DataTerkunciException).
Tes: tiap komponen, kombinasi lengkap, idempotensi, total, lunas immutable, tanpa konsultasi untuk lab mandiri.
```

### 6.2 PembayaranService (konfirmasi lunas + kunci data)
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: PembayaranService::bayar(Tagihan, MetodeBayar, jumlah, Pengguna kasir, ?noReferensi).
Dokumen: business-rules §9 (konfirmasi lunas), §1, §10 no.5–7; decisions D13.
Aturan: hanya jika kunjungan berstatus kasir; boleh beberapa pembayaran, lunas ketika Σ ≥ total;
kembalian dihitung; SATU transaksi saat lunas: tagihan lunas, kunjungan selesai (StatusKunjunganService),
erm_pemeriksaan.dikunci=1 + dikunci_pada, lab_permintaan.dikunci=1; pembayaran pada tagihan lunas ditolak;
catat sys_log_aktivitas. Di Service ini JANGAN ada cara membuka kunci.
Tes: lunas menguncikan semuanya; kurang bayar tidak menyelesaikan; status bukan kasir ditolak;
atomik (gagal di tengah → tidak ada yang terkunci); setelah lunas, upaya ubah SOAP/hasil lab lewat service ditolak.
```

### 6.3 UI Kasir
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: menu kasir.tagihan dan kasir.pembayaran: daftar tagihan (belum_bayar), detail per komponen,
form pembayaran (metode dari mtr_metode_bayar, jumlah, kembalian), kwitansi cetak.
Logika hanya lewat TagihanService/PembayaranService. Hak: kasir (L pada tagihan, LT pada pembayaran).
Tes: izin per peran, tidak bisa membayar saat status bukan kasir, kwitansi memuat data benar.
```

### 6.4 Integrasi Retur Pasien ke Tagihan
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: sambungkan retur pasien (Fase 5.8) ke tagihan: sesuaikan bil_tagihan_detail (baris pengurang) dan total.
Dokumen: business-rules §8.4 & §9, decisions Q4 (default: tidak setelah lunas).
Tes: tagihan belum lunas disesuaikan, idempotent, lunas ditolak (sesuai default Q4), total benar.
```

### 6.5 Laporan Kasir Harian
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: menu kasir.laporan: ringkasan harian per metode bayar dan per kasir, daftar transaksi, cetak.
Aturan: query teragregasi di database (bukan loop di PHP), filter tanggal, indeks sesuai database.md.
Tes: total laporan = Σ bil_pembayaran pada tanggal itu; izin.
```

### 6.6 P-AUDIT Fase 6

---

# FASE 7 — Integrasi SATUSEHAT
Dokumen utama: `satusehat.md`. **Jangan menebak URL/format API; semua detail teknis yang belum ada di dokumen harus berupa TODO yang diverifikasi dari dokumentasi resmi Kemenkes.**

### 7.1 Client, Konfigurasi, Token
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: interface SatuSehatClient, implementasi nyata (HTTP client bawaan Laravel), FakeSatuSehatClient untuk tes,
config/satusehat.php (dari env SATUSEHAT_*), dan pengelolaan token di sts_token (terenkripsi, refresh sebelum kedaluwarsa).
Dokumen: satusehat.md, architecture.md §11.
Aturan: kredensial hanya dari .env; JANGAN menulis endpoint/profil FHIR dari ingatan — beri TODO(verifikasi-resmi)
dan jelaskan apa yang harus kucocokkan. Tes memakai FakeSatuSehatClient (tanpa jaringan).
Tes: token diperbarui saat hampir kedaluwarsa, token tersimpan terenkripsi, fake client mengembalikan skenario sukses/gagal.
```

### 7.2 Sinkron Master (IHS Pasien/Dokter/Lokasi)
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: service untuk menyelesaikan ihs_number pasien (via NIK), ihs_practitioner_id dokter, ihs_location_id poli,
disimpan di kolom lokal agar tidak dicari ulang.
Dokumen: satusehat.md (pemetaan & prasyarat). Aturan: NIK tidak boleh masuk log; kegagalan menghasilkan pesan
jelas (mis. "NIK pasien tidak ditemukan di SATUSEHAT") untuk ditampilkan di monitor. Tes dengan fake client.
```

### 7.3 Listener + SatuSehatSyncJob
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: listener PemeriksaanDisimpan (decisions D14) yang membuat baris sts_sinkronisasi (Encounter & Condition,
status menunggu) lalu dispatch SatuSehatSyncJob ke queue 'satusehat'; job: tries=3, backoff [60,300,900],
ShouldBeUnique per (jenis_resource, referensi), idempotent.
Dokumen: satusehat.md (alur, retry, klasifikasi error [USULAN]), PRD §4.
Aturan: kirim Encounter dulu, lalu Condition yang merujuk Encounter; jika hanya Condition gagal, retry HANYA Condition;
setelah 3 gagal → failed_jobs + status gagal + pesan galat; request web TIDAK PERNAH menunggu API;
praktisi Encounter untuk lab mandiri = lab_permintaan.dokter_id.
Tes (fake client): sukses, gagal sementara lalu sukses, gagal permanen → failed_jobs, Encounter ok/Condition gagal,
idempotensi (job dijalankan dua kali tidak menggandakan), prasyarat IHS belum ada, simpan data medis tetap sukses
walau SATUSEHAT mati.
```

### 7.4 Monitor & Manual Re-sync
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: menu satusehat.sinkronisasi: ringkasan (menunggu/berhasil/gagal hari ini), tabel gagal dengan pesan galat,
filter tanggal/jenis, tombol Re-sync (satuan & massal) → status menunggu + dispatch ulang, badge jumlah gagal di dasbor admin.
Aturan: payload/respons (data pribadi) tidak ditampilkan penuh kecuali hak khusus dan tidak masuk log.
Tes: izin, re-sync mengubah status & mendispatch, massal hanya yang gagal.
```

### 7.5 Checklist Uji Sandbox (tanpa kode)
```
Protokol sesi (PEMBUKA) berlaku. JANGAN menulis kode.
Tugas: buat docs/uji-sandbox-satusehat.md berisi checklist langkah uji end-to-end di sandbox
(registrasi organisasi, kredensial, IHS pasien/dokter/lokasi uji, kirim Encounter+Condition, simulasi
gangguan jaringan untuk memeriksa retry, simulasi 4xx validasi), kolom hasil yang kuisi manual, dan daftar
TODO(verifikasi-resmi) dari kode yang harus dicocokkan dengan dokumentasi Kemenkes.
```

### 7.6 P-AUDIT Fase 7

---

# FASE 8 — Hardening & Rilis

### 8.1 Audit Performa
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: audit performa terhadap target < 2 detik di LAN (architecture.md §10). Ubah kode HANYA untuk perbaikan
yang kamu usulkan dan kusetujui.
Langkah: (1) isi database dengan data besar (seeder/factory: ≥10.000 pasien, ≥50.000 kunjungan, ≥5.000 batch stok);
(2) ukur halaman utama tiap modul (jumlah query, waktu); (3) temukan N+1, query tanpa indeks (EXPLAIN),
pagination hilang; (4) laporkan tabel temuan + usulan (indeks lewat migration BARU, eager loading, cache);
(5) tunggu persetujuan, terapkan, ukur ulang. Tes yang menghitung jumlah query untuk halaman kritis.
```

### 8.2 Audit Keamanan & Cakupan Izin
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: (a) buat tes otomatis yang MENELUSURI SEMUA route terautentikasi dan memastikan tiap route
memakai middleware izin (atau masuk daftar putih eksplisit seperti login/logout/dasbor);
(b) tes bahwa pengguna nonaktif ditolak; (c) pemeriksaan kolom sensitif (nik, token terenkripsi di DB; kolom kata sandi
ter-hash sesuai skema project saat ini); (d) pindai kode untuk Log::/logger() yang mungkin memuat NIK/SOAP/hasil lab;
(e) pastikan .env/kredensial tidak ada di repo; (f) cek throttle login dan rotasi sesi.
Laporkan temuan berurutan kritis→ringan, tunggu persetujuan sebelum memperbaiki.
```

### 8.3 Deploy, Backup, Operasional
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: buat docs/deploy.md dan contoh konfigurasi di folder deploy/: Nginx, Supervisor (queue worker --queue=satusehat,default),
cron scheduler, skrip backup MySQL harian + retensi + salinan offsite terenkripsi, skrip restore, urutan deploy
(composer install --no-dev, migrate --force, menu:sinkron, queue:restart, cache config/route/view), dan checklist
pemulihan bencana. Jangan menaruh rahasia nyata; gunakan placeholder. Sertakan langkah uji restore.
```

### 8.4 Panduan Pengguna & Skrip UAT
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: buat docs/uat.md: skenario uji penerimaan per peran (resepsionis, perawat, dokter tiap poli, petugas lab, apoteker,
kasir, admin) berdasarkan PRD dan business-rules, dengan langkah, hasil yang diharapkan, dan kolom lulus/gagal;
serta panduan singkat pengguna per peran (maks. 1 halaman masing-masing, bahasa Indonesia sederhana).
Sertakan skenario negatif: stok kurang, NIK ganda, data terkunci, peran tanpa hak, SATUSEHAT mati.
```

### 8.5 Audit Akhir terhadap Dokumen
```
Protokol sesi (PEMBUKA) berlaku.
Tugas: audit akhir seluruh proyek: bandingkan kode dengan SEMUA dokumen docs/*. Untuk setiap penyimpangan,
tentukan: perbaiki kode atau perbarui dokumen (rekomendasikan salah satu dan alasannya).
Verifikasi 8 invarian business-rules §10 punya tes dan lulus. Daftar semua TODO(Qn) dan TODO(verifikasi-resmi)
yang tersisa beserta pertanyaan yang harus dijawab pemilik proyek sebelum go-live. Tampilkan; jangan mengubah file.
```

---

## Lampiran: mencegah AI "lari"
- **Satu sesi = satu fase** bila memungkinkan; sesi panjang membuat AI melupakan aturan. Gunakan P-LANJUT untuk sesi baru.
- **Wajibkan Rencana → Persetujuan → Kode** (PEMBUKA no.1). Tolak rencana yang menyentuh file di luar tugas.
- **Kecurigaan cepat** setelah tiap tugas: `git diff --stat` (apakah file di luar cakupan berubah?), `grep -rn "jumlah_sisa" app/ | grep -v StokService`, `grep -rn "status" app/Http/Controllers | grep -i "update\|="`, `grep -rn "Log::" app/`.
- **Jangan menerima "sudah mengikuti aturan" tanpa CHECKLIST KEPATUHAN** yang terisi per aturan.
- **Perbarui dokumen lebih dulu** jika Anda mengubah aturan; kode mengikuti dokumen, bukan sebaliknya.
