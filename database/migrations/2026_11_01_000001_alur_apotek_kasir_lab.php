<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rekonsiliasi alur baru apotek/kasir/lab (D34-D43).
 * Lihat docs/database.md ("Migration 2026_11_01_000001_alur_apotek_kasir_lab").
 * Belum ada logika billing; perubahan ini hanya skema + fondasi model.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- reg_kunjungan.status: buang 'kasir' (baris lama dipetakan ke 'farmasi') ----
        DB::table('reg_kunjungan')->where('status', 'kasir')->update(['status' => 'farmasi']);
        DB::statement("ALTER TABLE reg_kunjungan MODIFY status ENUM('terdaftar','ttv','menunggu','diperiksa','lab','farmasi','selesai','batal') NOT NULL DEFAULT 'terdaftar'");

        // ---- Tabel baru: shift kasir & setoran dokter ----
        Schema::create('bil_shift', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->constrained('sys_pengguna', 'id', 'fk_bil_shift_pengguna');
            $table->enum('status', ['terbuka', 'tertutup'])->default('terbuka');
            $table->timestamp('dibuka_pada')->useCurrent();
            $table->timestamp('ditutup_pada')->nullable();
            $table->foreignId('ditutup_oleh')->nullable()->constrained('sys_pengguna', 'id', 'fk_bil_shift_ditutup');
            $table->string('alasan_penutupan', 255)->nullable();
            $table->timestamps();

            $table->index(['pengguna_id'], 'idx_bil_shift_pengguna');
        });

        Schema::create('bil_setoran_dokter', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('bil_shift', 'id', 'fk_bil_setoran_shift');
            $table->foreignId('dokter_id')->constrained('mtr_dokter', 'id', 'fk_bil_setoran_dokter');
            $table->enum('metode', ['tunai', 'transfer'])->default('tunai');
            $table->string('no_referensi', 100)->nullable();
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->decimal('jumlah_tunai', 15, 2)->nullable();
            $table->decimal('jumlah_transfer', 15, 2)->nullable();
            $table->foreignId('diserahkan_oleh')->nullable()->constrained('sys_pengguna', 'id', 'fk_bil_setoran_oleh');
            $table->timestamps();

            $table->unique(['shift_id', 'dokter_id'], 'uk_bil_setoran_dokter');
        });

        // ---- bil_tagihan: jenis + unik (kunjungan_id, jenis) ----
        Schema::table('bil_tagihan', function (Blueprint $table) {
            $table->enum('jenis', ['farmasi', 'lab'])->default('farmasi');
            // FK kunjungan_id menopang pada index unik lama; lepas dulu sebelum mengganti index.
            $table->dropForeign('fk_bil_tagihan_kunjungan');
            $table->dropUnique('uk_bil_tagihan_kunjungan');
            $table->unique(['kunjungan_id', 'jenis'], 'uk_bil_tagihan_kunjungan_jenis');
            $table->foreign('kunjungan_id', 'fk_bil_tagihan_kunjungan')->references('id')->on('reg_kunjungan');
        });

        // ---- bil_pembayaran: shift + transfer ----
        Schema::table('bil_pembayaran', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->after('metode_bayar_id')
                ->constrained('bil_shift', 'id', 'fk_bil_pembayaran_shift');
            $table->string('nama_bank', 100)->nullable()->after('no_referensi');
            $table->string('nama_pengirim', 100)->nullable()->after('nama_bank');
        });

        // ---- bil_tagihan_detail.komponen: tambah embalase/uang_kopi/simpanan ----
        DB::statement("ALTER TABLE bil_tagihan_detail MODIFY komponen ENUM('konsultasi','tindakan','lab','obat','jasa_racik','embalase','uang_kopi','simpanan','lainnya') NOT NULL");

        // ---- mtr_obat: zat_aktif/komposisi; hapus harga_jual (pindah ke batch) ----
        Schema::table('mtr_obat', function (Blueprint $table) {
            $table->string('zat_aktif', 255)->nullable();
            $table->text('komposisi')->nullable();
            $table->dropColumn('harga_jual');
        });

        Schema::table('mtr_pemeriksaan_lab', function (Blueprint $table) {
            $table->enum('kategori', ['lab', 'radiologi'])->default('lab');
        });

        Schema::table('reg_ttv', function (Blueprint $table) {
            $table->unsignedSmallInteger('frekuensi_napas')->nullable()->comment('x/menit');
            $table->decimal('gula_darah', 6, 1)->nullable()->comment('mg/dL');
            $table->unsignedTinyInteger('skala_nyeri')->nullable()->comment('0-10');
        });

        Schema::table('erm_pemeriksaan', function (Blueprint $table) {
            $table->decimal('tarif_konsultasi', 15, 2)->default(0);
            $table->text('riwayat_kesehatan')->nullable();
        });

        Schema::table('apt_penerimaan', function (Blueprint $table) {
            $table->decimal('ppn_persen', 5, 2)->nullable()->comment('kosong = HNA sudah termasuk PPN');
        });

        Schema::table('apt_stok_batch', function (Blueprint $table) {
            $table->decimal('harga_jual', 15, 2)->default(0);
        });

        Schema::table('apt_pengeluaran_resep', function (Blueprint $table) {
            $table->decimal('harga_jual_satuan', 15, 2)->default(0);
        });
    }

    public function down(): void
    {
        // Catatan: baris berstatus 'kasir' yang sudah diubah menjadi 'farmasi' TIDAK dipulihkan otomatis.
        Schema::table('apt_pengeluaran_resep', function (Blueprint $table) {
            $table->dropColumn('harga_jual_satuan');
        });

        Schema::table('apt_stok_batch', function (Blueprint $table) {
            $table->dropColumn('harga_jual');
        });

        Schema::table('apt_penerimaan', function (Blueprint $table) {
            $table->dropColumn('ppn_persen');
        });

        Schema::table('erm_pemeriksaan', function (Blueprint $table) {
            $table->dropColumn(['tarif_konsultasi', 'riwayat_kesehatan']);
        });

        Schema::table('reg_ttv', function (Blueprint $table) {
            $table->dropColumn(['frekuensi_napas', 'gula_darah', 'skala_nyeri']);
        });

        Schema::table('mtr_pemeriksaan_lab', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });

        Schema::table('mtr_obat', function (Blueprint $table) {
            $table->dropColumn(['zat_aktif', 'komposisi']);
            $table->decimal('harga_jual', 15, 2)->default(0);
        });

        DB::statement("ALTER TABLE bil_tagihan_detail MODIFY komponen ENUM('konsultasi','tindakan','lab','obat','jasa_racik','lainnya') NOT NULL");

        Schema::table('bil_pembayaran', function (Blueprint $table) {
            $table->dropForeign('fk_bil_pembayaran_shift');
            $table->dropColumn(['shift_id', 'nama_bank', 'nama_pengirim']);
        });

        Schema::table('bil_tagihan', function (Blueprint $table) {
            $table->dropForeign('fk_bil_tagihan_kunjungan');
            $table->dropUnique('uk_bil_tagihan_kunjungan_jenis');
            $table->dropColumn('jenis');
            $table->unique(['kunjungan_id'], 'uk_bil_tagihan_kunjungan');
            $table->foreign('kunjungan_id', 'fk_bil_tagihan_kunjungan')->references('id')->on('reg_kunjungan');
        });

        Schema::dropIfExists('bil_setoran_dokter');
        Schema::dropIfExists('bil_shift');

        DB::statement("ALTER TABLE reg_kunjungan MODIFY status ENUM('terdaftar','ttv','menunggu','diperiksa','lab','farmasi','kasir','selesai','batal') NOT NULL DEFAULT 'terdaftar'");
    }
};
