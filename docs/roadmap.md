# Roadmap — SIM Klinik Avicena

Ukuran: **S** (≤ 1 hari) · **M** (2–4 hari) · **L** (1–2 minggu) · **XL** (> 2 minggu). Perkiraan kasar untuk satu developer yang dibantu AI; sesuaikan dengan ritme Anda.

Status: ✅ selesai · 🔄 berjalan · ⬜ belum

## Fase 0 — Fondasi

| Status | Item                                                                                           | Ukuran |
| ------ | ---------------------------------------------------------------------------------------------- | ------ |
| ✅     | PRD v2.2                                                                                       | —      |
| ✅     | Rancangan infrastruktur & database (56 tabel dasar), `schema.sql`                              | —      |
| ✅     | Migration, model domain + `User` (`sys_pengguna`), `ModelDasar`, seeder awal                   | —      |
| ✅     | Manajemen menu & hak akses (`sys_menu`, `IzinService`, `izin:*`, `menu:sinkron`, UI Hak Akses) | —      |
| ✅     | Sidebar AdminLTE 4 dinamis per peran (rute yang belum ada aman jadi `#`)                       | —      |
| ✅     | Proyek Laravel 13 + paket terpasang, `migrate --seed` sukses di MySQL                          | S      |
| ✅     | Layout AdminLTE (`layouts.app`: header, sidebar, footer, flash message, breadcrumb)            | S      |
| ✅     | Login (username + kata sandi), logout, ingat saya                                              | S      |
| ✅     | Throttle login (5×/menit per akun+IP, 20×/menit per IP)                                        | S      |
| ✅     | Ganti kata sandi sendiri (form + tes)                                                          | S      |
| ✅     | `login_terakhir` diisi saat login (migration baru)                                             | S      |
| ✅     | Zona waktu `Asia/Jakarta`, locale `id`, `lang/id` (validasi, auth)                             | S      |
| ✅     | `.env.example` siap klinik (MySQL, timezone/locale, `NIK_HMAC_KEY`, `SATUSEHAT_*` sandbox)     | S      |
| ✅     | Log aktivitas manual: login, logout, simpan hak akses                                          | S      |
| ✅     | Pencatat `sys_log_aktivitas` otomatis (observer; master + pengguna + peran)                    | S      |
| ✅     | CRUD Pengguna & Peran (pengaman admin terakhir, sandi opsional saat ubah)                      | M      |
| ⬜     | CI: pint + test pada MySQL                                                                     | S      |

**Selesai bila**: admin login, sidebar muncul sesuai peran (diuji per peran), halaman Hak Akses menyimpan perubahan + tes 403, throttle & ganti kata sandi jalan, tes dasar lulus.
**Tercapai** (20 Okt 2026): log aktivitas otomatis dan CRUD Pengguna & Peran selesai; 160 tes hijau.

## Fase 1 — Data master

Master dibutuhkan semua modul, kerjakan lebih dulu. Semua CRUD memakai pola yang sama (buat satu contoh yang rapi, lalu replikasi).
| Status | Item | Ukuran |
|---|---|---|
| ✅ | Poli, Dokter, Metode Bayar, Satuan, Kategori Obat | M |
| ✅ | Obat (+ stok minimum, golongan, kode KFA opsional), Supplier | M |
| ✅ | Tindakan Medis, ICD-10 (impor CSV), Pemeriksaan Lab + Parameter Lab | M |
| ✅ | Menu Racikan (template + bahan) | M |
| ✅ | Pengaturan sistem (`sys_pengaturan`: jasa racik flat, kebijakan retur pasien, dll.) | S |

**Selesai bila**: seluruh master bisa dikelola sesuai hak akses, impor ICD-10 berjalan, ada tes validasi dasar.
**Tercapai** (20 Okt 2026): 131 tes hijau, semua halaman master memakai middleware `izin` dan terdaftar di `config/menu.php`.

## Fase 2 — Pendaftaran & antrean

| Status | Item                                                                                | Ukuran |
| ------ | ----------------------------------------------------------------------------------- | ------ |
| ✅     | `PendaftaranService`: cari pasien via `nik_hash`, daftar pasien baru, buat nomor RM | M      |
| ✅     | `AntreanService`: nomor harian per poli dengan lock (+ tes konkurensi)              | M      |
| ✅     | `KunjunganService`: nomor kunjungan atomik, simpan TTV + antrean, event domain      | S      |
| ✅     | `StatusKunjunganService`: matriks transisi + `sinkron()` lab/farmasi                | M      |
| ⬜     | Form pendaftaran kunjungan poli + cetak nomor antrean (thermal/print browser)       | M      |
| ⬜     | Input TTV (UI, wajib sebelum poli)                                                  | S      |
| ⬜     | Daftar/panggil antrean per poli, ubah status                                        | M      |
| ⬜     | (Opsional) layar TV antrean                                                         | S      |

**Selesai bila**: pasien baru/lama bisa didaftarkan, nomor `INT-001` dst. unik & reset harian meski 2 petugas mendaftar bersamaan, TTV tersimpan, status kunjungan berpindah benar.
**Tercapai sebagian** (24 Okt 2026): `PendaftaranService`, `AntreanService`, `KunjunganService`, dan `StatusKunjunganService` selesai; seluruh tes hijau. Berikutnya form pendaftaran + cetak nomor, TTV UI, lalu daftar/panggil antrean.

## Fase 3 — Rekam medis & e-resep

| Item                                                                       | Ukuran |
| -------------------------------------------------------------------------- | ------ |
| Form SOAP + diagnosis ICD-10 (autocomplete)                                | M      |
| Form khusus: Penyakit Dalam, Saraf                                         | M      |
| Odontogram digital poli gigi (komponen grafis, simpan ke `erm_odontogram`) | L      |
| Tindakan medis                                                             | S      |
| e-Resep (reguler + racikan dari template)                                  | L      |
| Rujukan lab internal                                                       | M      |
| Riwayat kunjungan pasien                                                   | M      |
| Penguncian pemeriksaan (dipanggil dari Billing nanti)                      | S      |

**Selesai bila**: dokter tiap poli menyelesaikan pemeriksaan end-to-end, e-resep muncul di farmasi, rujukan lab masuk antrean `LAB-xxx` otomatis.

## Fase 4 — Laboratorium

| Item                                                                                          | Ukuran |
| --------------------------------------------------------------------------------------------- | ------ |
| Antrean lab dari rujukan (otomatis)                                                           | S      |
| Pendaftaran lab mandiri (cari/daftar pasien via NIK, **pilih dokter lab**, pilih pemeriksaan) | M      |
| Pencatatan spesimen (`lab_spesimen`: status, waktu, petugas) + tautan ke detail               | M      |
| Input hasil per parameter + penanda normal/rendah/tinggi                                      | M      |
| Kunci hasil → kirim komponen biaya ke tagihan                                                 | S      |
| Cetak hasil lab                                                                               | S      |

**Status 21 Okt 2026**: tabel `lab_spesimen`, model `Lab\Spesimen`, dan menu `lab.spesimen` sudah ada; alur/UI belum.
**Status 28 Okt 2026**: layanan domain Lab selesai — `LabService` (mulaiProses, simpanHasil snapshot + penanda, kunci + sinkron status, event `LabDikunci`), `SpesimenService` (catat pengambilan, ubah status D22, tautan ke detail, event `SpesimenDiambil`), dan `LabMandiriService` (pendaftaran lab mandiri, antrean LAB, event `LabDiminta`). UI Lab/pendaftaran masih ⬜.

**Selesai bila**: kedua alur (rujukan & mandiri) sampai tagihan; hasil terkunci tidak bisa diubah; `dokter_id` wajib sebelum kunci.

## Fase 5 — Apotek, farmasi, inventaris

Fase paling berisiko: kerjakan `StokService` + tes dulu, baru UI.
| Item | Ukuran |
|---|---|
| `StokService`: tambah batch, kurangi FEFO, mutasi, tes (habis, lintas batch, kedaluwarsa, konkurensi) | L |
| Obat masuk (faktur, bets, kedaluwarsa, harga beli) | M |
| Kartu stok / laporan mutasi, peringatan stok minimum & mendekati kedaluwarsa | M |
| Validasi & penyerahan e-resep reguler | M |
| Racikan: potong stok per bahan saat selesai diracik + jasa racik flat | L |
| Penjualan umum (tanpa resep) | M |
| Retur ke supplier & retur dari pasien | L |
| Stock opname + varians | M |

**Selesai bila**: stok sistem selalu = jumlah mutasi; FEFO benar; racikan memotong semua bahan atomik (semua atau tidak sama sekali); retur & opname tercatat di mutasi.

## Fase 6 — Billing / kasir

| Item                                                                                 | Ukuran |
| ------------------------------------------------------------------------------------ | ------ |
| `TagihanService`: konsolidasi otomatis (konsultasi, tindakan, lab, obat, jasa racik) | L      |
| Pembayaran (multi metode, kembalian), cetak kwitansi                                 | M      |
| Konfirmasi bayar → status `selesai` + kunci rekam medis                              | M      |
| Tagihan penjualan apotek umum                                                        | S      |
| Laporan kasir harian                                                                 | M      |

**Selesai bila**: satu kunjungan menghasilkan satu tagihan lengkap & akurat; setelah lunas data medis terkunci; laporan harian cocok dengan pembayaran.

## Fase 7 — Integrasi SATUSEHAT (dipecah)
| Sub-fase | Isi | Ukuran |
|---|---|---|
| **7.0 Pemetaan terminologi** 🔄 | kolom pemetaan di form master (pemeriksaan/parameter lab→LOINC/SNOMED/UCUM, poli→tipe pelayanan; obat→KFA opsional & tidak dipakai selama D33), CRUD `mtr_nakes` & `mtr_lokasi`, laporan "master belum terpetakan" | L |
| **7A Fondasi + Tahap A** | client/token, sinkron IHS master, siklus Encounter, listener & ketergantungan | XL |
| **7B Obat** | ⬜ ditunda: Medication/MedicationRequest/MedicationDispense di luar cakupan (D33) | — |
| **7C Lab + TTV** | ServiceRequest, Specimen, Observation lab, Observation TTV | XL |
| **7D Resume medis** | Composition (referensi Encounter, Observation, ServiceRequest, Specimen + narasi SOAP) | M |
| **7E Monitor & uji sandbox** | monitor, Re-sync, rasio Composition:Encounter, checklist sandbox | M |

**Status 21 Okt 2026**: 7.0 🔄 — migration pemetaan, model `Nakes`/`Lokasi`/`Spesimen`/`KunjunganRiwayatStatus`, menu `master.nakes`/`master.lokasi`/`satusehat.*`, dan seeder `APT` sudah ada; CRUD master, form pemetaan, dan laporan "belum terpetakan" belum.
**Status 29 Okt 2026**: cakupan dikunci ke Encounter, Observation, Composition, ServiceRequest, Specimen (D33). Condition dan Medication* tidak dikerjakan; jangan menambahkan kolom/Job untuk keduanya.

**Selesai bila**: simpan data medis tidak menunggu API; gangguan SATUSEHAT tidak mengganggu pelayanan; data gagal bisa disinkron ulang manual.

## Fase 8 — Hardening & rilis

| Item                                                                     | Ukuran |
| ------------------------------------------------------------------------ | ------ |
| Uji performa (< 2 detik LAN), perbaikan N+1/indeks                       | M      |
| Uji keamanan: RBAC, enkripsi, log tanpa data sensitif                    | M      |
| Backup otomatis + uji restore, monitoring                                | M      |
| UAT dengan staf klinik tiap peran, perbaikan                             | L      |
| Migrasi data (jika ada data lama), pelatihan, go-live, masa pendampingan | L      |

## Urutan & ketergantungan

`Fase 0 → 1 → 2 → 3 → (4 ∥ 5) → 6 → 7 → 8`. Fase 4 dan 5 bisa paralel setelah Fase 3. Fase 7 bisa dimulai lebih awal (sandbox) begitu Fase 3 selesai, tapi rilis menunggu Fase 6.

## Di luar lingkup (belum diminta di PRD)

Pendaftaran online, BPJS/asuransi, multi-cabang, aplikasi mobile pasien, integrasi alat lab otomatis. Jangan dikerjakan tanpa keputusan baru di `decisions.md`.


Catatan lintas fase (kerjakan di fase asalnya, bukan menunggu Fase 7): **Fase 2** mencatat `waktu_datang` + riwayat status; **Fase 3** form resep dosis terstruktur + `waktu_mulai/selesai` + cara keluar; **Fase 4** pencatatan spesimen; **Fase 5** event `ResepDiserahkan`. Lihat `prompts.md` (tambahan sudah disisipkan di 2.3, 3.1, 3.6, 3.7, 4.1–4.3, dan 5.4).