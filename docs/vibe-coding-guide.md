# Panduan Vibe Coding — SIM Klinik Avicena

Tujuan: membuat AI menghasilkan kode yang konsisten dengan arsitektur, tanpa Anda mengulang konteks setiap kali.

## Setup sekali
1. Taruh `AGENTS.md` di root repo (salin sebagai `CLAUDE.md` jika memakai Claude Code) dan folder `docs/` di repo yang sama.
2. Pastikan `docs/PRD.md`, `docs/database.md`, `docs/business-rules.md` selalu terbaru.
3. Commit awal sebelum mulai agar mudah rollback.

## Ritme kerja per fitur ("irisan vertikal")
1. **Pilih satu item** dari `roadmap.md` (satu fase, satu irisan: migration → service → controller → view → tes).
2. **Minta rencana dulu**, belum kode: "Baca AGENTS.md dan docs terkait, lalu tulis rencana langkah, file yang akan dibuat/diubah, dan pertanyaan yang belum jelas."
3. Setujui/ubah rencana → **minta implementasi bertahap** (service + tes dulu untuk logika kritis).
4. Jalankan `php artisan test`, `vendor/bin/pint`, buka halaman di browser.
5. Review diff, commit kecil. Perbarui `roadmap.md` (centang) dan `decisions.md` bila ada keputusan baru.

## Template prompt
```
Konteks: baca AGENTS.md, docs/architecture.md, docs/business-rules.md (bagian <X>), docs/conventions.md.
Tugas: <satu kemampuan, mis. "buat AntreanService::ambilNomor + tes konkurensi">.
Batasan: ikuti penamaan Indonesia & prefix modul; logika di Service; transaksi DB; jangan tambah package;
jangan ubah migration yang sudah ada (buat migration baru jika perlu).
Keluaran: rencana singkat dulu, lalu kode + tes. Sebutkan asumsi yang diambil.
Selesai bila: <kriteria dari roadmap>.
```

## Contoh prompt siap pakai
- **Fase 1 — pola CRUD master (buat 1 contoh rapi dulu)**: "Buat CRUD `Master\Satuan` lengkap: FormRequest, Controller dengan `HasMiddleware` (`izin:master.satuan,<aksi>` — tambahkan menu di config/menu.php), view AdminLTE index/create/edit dengan pagination, flash message, tes izin 403. Ini akan jadi template CRUD master lainnya."
- **Fase 2 — antrean**: "Implementasikan `Registrasi\AntreanService::ambilNomor` sesuai business-rules §3 dan conventions. Tulis tes: nomor berurutan, reset hari berikutnya, dua proses paralel tidak menghasilkan nomor ganda."
- **Fase 5 — stok**: "Implementasikan `Apotek\StokService` (tambahBatch, kurangi FEFO, mutasi) sesuai business-rules §8. Tes: lintas batch, batch kedaluwarsa dilewati, stok kurang → exception & tidak ada perubahan, invarian jumlah_sisa = Σ mutasi."
- **Fase 6 — tagihan**: "Buat `Billing\TagihanService::susunDariKunjungan` idempotent (updateOrCreate per referensi) sesuai business-rules §9 dengan tes total."
- **Fase 7 — SATUSEHAT**: "Buat `SatuSehatSyncJob` + `SatuSehatClient` (interface) + `FakeSatuSehatClient` sesuai docs/satusehat.md. Jangan menebak endpoint; beri TODO untuk diverifikasi dari dokumentasi resmi."

## Aturan main dengan AI
- **Satu tugas per sesi/prompt.** Prompt besar ("buatkan semua modul apotek") menghasilkan kode rapuh.
- **Tes dulu untuk logika kritis** (antrean, stok, tagihan, kunci, izin). Minta AI menulis tes, lalu implementasi.
- **Jangan biarkan AI menebak aturan bisnis.** Jika ada di "Pertanyaan terbuka", minta AI bertanya atau memakai default yang tertulis dan menandainya.
- **Awasi pelanggaran umum**: logika di controller, update stok langsung, status diubah tanpa validasi transisi, lupa middleware `izin`, N+1 query, float untuk uang, log berisi NIK, package baru tanpa izin, mengubah migration lama.
- **Selalu `migrate:fresh --seed` di lokal** setelah AI menyentuh migration, lalu jalankan tes.
- **Perbarui dokumen** bila arsitektur/aturan berubah; AI hanya sepandai konteksnya.

## Checklist review diff
- [ ] Penamaan Indonesia + prefix; `created_at/updated_at/deleted_at` tetap
- [ ] Controller tipis, logika di Service, transaksi DB
- [ ] `izin` terpasang & menu terdaftar; Policy untuk data
- [ ] Stok hanya lewat `StokService`; antrean lewat `AntreanService`
- [ ] Tidak ada NIK/hasil lab/SOAP di log; kolom sensitif terenkripsi
- [ ] Eager loading + pagination; tidak ada N+1
- [ ] Tes ditulis & lulus; `pint` bersih
- [ ] Dokumen (roadmap/decisions) diperbarui

## Urutan awal yang disarankan (7 hari pertama)
1. Pasang proyek + paket tahap 2 → `migrate --seed` → login → sidebar tampil.
2. Layout AdminLTE, login, flash message, log aktivitas.
3. CRUD `Satuan` sebagai template → replikasi master lain.
4. Tes izin & Hak Akses UI end-to-end.
5. Mulai Fase 2 (`PendaftaranService` + `AntreanService`).
