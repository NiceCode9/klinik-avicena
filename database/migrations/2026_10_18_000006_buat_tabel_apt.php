<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modul apt_ (Apotek) - 13 tabel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apt_penerimaan', function (Blueprint $table) {
            $table->id();
            $table->string('no_faktur', 50);
            $table->foreignId('supplier_id')->constrained('mtr_supplier', 'id', 'fk_apt_penerimaan_supplier');
            $table->date('tanggal_faktur');
            $table->date('tanggal_terima');
            $table->decimal('total', 15, 2)->default(0);
            $table->string('catatan', 255)->nullable();
            $table->foreignId('penerima_id')->nullable()->constrained('sys_pengguna', 'id', 'fk_apt_penerimaan_penerima');
            $table->timestamps();

            $table->unique(['supplier_id', 'no_faktur'], 'uk_apt_penerimaan_faktur');
        });

        Schema::create('apt_penerimaan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerimaan_id')->constrained('apt_penerimaan', 'id', 'fk_apt_penerimaan_det_penerimaan')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('mtr_obat', 'id', 'fk_apt_penerimaan_det_obat');
            $table->string('nomor_bets', 50);
            $table->date('tanggal_kedaluwarsa');
            $table->decimal('jumlah', 12, 2);
            $table->decimal('harga_beli', 15, 2);
            $table->timestamps();

            $table->index(['penerimaan_id'], 'idx_apt_penerimaan_det_penerimaan');
        });

        Schema::create('apt_stok_batch', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obat_id')->constrained('mtr_obat', 'id', 'fk_apt_stok_batch_obat');
            $table->foreignId('penerimaan_detail_id')->nullable()->constrained('apt_penerimaan_detail', 'id', 'fk_apt_stok_batch_penerimaan');
            $table->string('nomor_bets', 50);
            $table->date('tanggal_kedaluwarsa');
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('jumlah_masuk', 12, 2);
            $table->decimal('jumlah_sisa', 12, 2);
            $table->timestamps();

            $table->index(['obat_id', 'tanggal_kedaluwarsa'], 'idx_apt_stok_fefo');
        });

        DB::statement('ALTER TABLE apt_stok_batch ADD CONSTRAINT ck_apt_stok_batch_sisa CHECK (jumlah_sisa >= 0)');

        Schema::create('apt_mutasi_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_batch_id')->constrained('apt_stok_batch', 'id', 'fk_apt_mutasi_batch');
            $table->foreignId('obat_id')->constrained('mtr_obat', 'id', 'fk_apt_mutasi_obat');
            $table->enum('jenis_mutasi', ['penerimaan', 'resep', 'racikan', 'penjualan_umum', 'retur_supplier', 'retur_pasien', 'opname_plus', 'opname_minus']);
            $table->decimal('jumlah', 12, 2)->comment('positif = masuk, negatif = keluar');
            $table->decimal('saldo_setelah', 12, 2)->comment('saldo batch setelah mutasi');
            $table->string('referensi_tipe', 50)->nullable();
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('sys_pengguna', 'id', 'fk_apt_mutasi_oleh');
            $table->timestamps();

            $table->index(['obat_id', 'created_at'], 'idx_apt_mutasi_obat');
            $table->index(['stok_batch_id'], 'idx_apt_mutasi_batch');
            $table->index(['referensi_tipe', 'referensi_id'], 'idx_apt_mutasi_ref');
        });

        Schema::create('apt_pengeluaran_resep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resep_item_id')->constrained('erm_resep_item', 'id', 'fk_apt_pengeluaran_item');
            $table->foreignId('obat_id')->constrained('mtr_obat', 'id', 'fk_apt_pengeluaran_obat');
            $table->foreignId('stok_batch_id')->constrained('apt_stok_batch', 'id', 'fk_apt_pengeluaran_batch');
            $table->decimal('jumlah', 12, 3);
            $table->timestamps();

            $table->index(['resep_item_id'], 'idx_apt_pengeluaran_item');
        });

        Schema::create('apt_penjualan_umum', function (Blueprint $table) {
            $table->id();
            $table->string('no_penjualan', 30);
            $table->foreignId('kunjungan_id')->nullable()->constrained('reg_kunjungan', 'id', 'fk_apt_penjualan_kunjungan');
            $table->foreignId('pasien_id')->nullable()->constrained('mtr_pasien', 'id', 'fk_apt_penjualan_pasien');
            $table->string('nama_pembeli', 150)->nullable();
            $table->date('tanggal');
            $table->decimal('total', 15, 2)->default(0);
            $table->enum('status', ['draf', 'menunggu_bayar', 'lunas', 'batal'])->default('draf');
            $table->foreignId('petugas_id')->nullable()->constrained('sys_pengguna', 'id', 'fk_apt_penjualan_petugas');
            $table->timestamps();

            $table->unique(['no_penjualan'], 'uk_apt_penjualan_no');
        });

        Schema::create('apt_penjualan_umum_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->constrained('apt_penjualan_umum', 'id', 'fk_apt_penjualan_det_penjualan')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('mtr_obat', 'id', 'fk_apt_penjualan_det_obat');
            $table->foreignId('stok_batch_id')->constrained('apt_stok_batch', 'id', 'fk_apt_penjualan_det_batch');
            $table->decimal('jumlah', 12, 2);
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->index(['penjualan_id'], 'idx_apt_penjualan_det');
        });

        Schema::create('apt_retur_supplier', function (Blueprint $table) {
            $table->id();
            $table->string('no_retur', 30);
            $table->foreignId('supplier_id')->constrained('mtr_supplier', 'id', 'fk_apt_retur_supplier_supplier');
            $table->foreignId('penerimaan_id')->nullable()->comment('faktur asal')->constrained('apt_penerimaan', 'id', 'fk_apt_retur_supplier_penerimaan');
            $table->enum('alasan', ['rusak', 'cacat', 'mendekati_kedaluwarsa', 'kedaluwarsa', 'lainnya']);
            $table->date('tanggal');
            $table->enum('status', ['draf', 'dikirim', 'diterima_supplier', 'batal'])->default('draf');
            $table->string('catatan', 255)->nullable();
            $table->foreignId('petugas_id')->nullable()->constrained('sys_pengguna', 'id', 'fk_apt_retur_supplier_petugas');
            $table->timestamps();

            $table->unique(['no_retur'], 'uk_apt_retur_supplier_no');
        });

        Schema::create('apt_retur_supplier_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_id')->constrained('apt_retur_supplier', 'id', 'fk_apt_retur_sup_det_retur')->cascadeOnDelete();
            $table->foreignId('stok_batch_id')->constrained('apt_stok_batch', 'id', 'fk_apt_retur_sup_det_batch');
            $table->decimal('jumlah', 12, 2);
            $table->timestamps();
        });

        Schema::create('apt_retur_pasien', function (Blueprint $table) {
            $table->id();
            $table->string('no_retur', 30);
            $table->foreignId('pasien_id')->nullable()->constrained('mtr_pasien', 'id', 'fk_apt_retur_pasien_pasien');
            $table->foreignId('resep_id')->nullable()->constrained('erm_resep', 'id', 'fk_apt_retur_pasien_resep');
            $table->foreignId('penjualan_id')->nullable()->constrained('apt_penjualan_umum', 'id', 'fk_apt_retur_pasien_penjualan');
            $table->string('alasan', 255);
            $table->date('tanggal');
            $table->decimal('total_pengembalian', 15, 2)->default(0);
            $table->foreignId('disetujui_oleh')->nullable()->constrained('sys_pengguna', 'id', 'fk_apt_retur_pasien_setuju');
            $table->timestamps();

            $table->unique(['no_retur'], 'uk_apt_retur_pasien_no');
        });

        Schema::create('apt_retur_pasien_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_id')->constrained('apt_retur_pasien', 'id', 'fk_apt_retur_pas_det_retur')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('mtr_obat', 'id', 'fk_apt_retur_pas_det_obat');
            $table->foreignId('stok_batch_id')->constrained('apt_stok_batch', 'id', 'fk_apt_retur_pas_det_batch');
            $table->decimal('jumlah', 12, 2);
            $table->decimal('nilai_kembali', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('apt_opname', function (Blueprint $table) {
            $table->id();
            $table->string('no_opname', 30);
            $table->date('tanggal');
            $table->foreignId('apoteker_id')->nullable()->constrained('sys_pengguna', 'id', 'fk_apt_opname_apoteker');
            $table->enum('status', ['draf', 'final'])->default('draf');
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->unique(['no_opname'], 'uk_apt_opname_no');
        });

        Schema::create('apt_opname_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opname_id')->constrained('apt_opname', 'id', 'fk_apt_opname_det_opname')->cascadeOnDelete();
            $table->foreignId('stok_batch_id')->constrained('apt_stok_batch', 'id', 'fk_apt_opname_det_batch');
            $table->decimal('stok_sistem', 12, 2);
            $table->decimal('stok_fisik', 12, 2);
            $table->decimal('selisih', 12, 2)->comment('stok_fisik - stok_sistem');
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique(['opname_id', 'stok_batch_id'], 'uk_apt_opname_detail');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('apt_opname_detail');
        Schema::dropIfExists('apt_opname');
        Schema::dropIfExists('apt_retur_pasien_detail');
        Schema::dropIfExists('apt_retur_pasien');
        Schema::dropIfExists('apt_retur_supplier_detail');
        Schema::dropIfExists('apt_retur_supplier');
        Schema::dropIfExists('apt_penjualan_umum_detail');
        Schema::dropIfExists('apt_penjualan_umum');
        Schema::dropIfExists('apt_pengeluaran_resep');
        Schema::dropIfExists('apt_mutasi_stok');
        Schema::dropIfExists('apt_stok_batch');
        Schema::dropIfExists('apt_penerimaan_detail');
        Schema::dropIfExists('apt_penerimaan');
        Schema::enableForeignKeyConstraints();
    }
};
