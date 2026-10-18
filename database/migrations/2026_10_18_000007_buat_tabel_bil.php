<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul bil_ (Billing) - 3 tabel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bil_tagihan', function (Blueprint $table) {
            $table->id();
            $table->string('no_tagihan', 30);
            $table->foreignId('kunjungan_id')->nullable()->constrained('reg_kunjungan', 'id', 'fk_bil_tagihan_kunjungan');
            $table->foreignId('penjualan_umum_id')->nullable()->constrained('apt_penjualan_umum', 'id', 'fk_bil_tagihan_penjualan');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->enum('status', ['belum_bayar', 'lunas', 'batal'])->default('belum_bayar');
            $table->timestamps();

            $table->unique(['no_tagihan'], 'uk_bil_tagihan_no');
            $table->unique(['kunjungan_id'], 'uk_bil_tagihan_kunjungan');
            $table->unique(['penjualan_umum_id'], 'uk_bil_tagihan_penjualan');
        });

        Schema::create('bil_tagihan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('bil_tagihan', 'id', 'fk_bil_tagihan_detail')->cascadeOnDelete();
            $table->enum('komponen', ['konsultasi', 'tindakan', 'lab', 'obat', 'jasa_racik', 'lainnya']);
            $table->string('referensi_tipe', 50)->nullable();
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->string('deskripsi', 255);
            $table->decimal('jumlah', 12, 2)->default(1);
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->index(['tagihan_id'], 'idx_bil_tagihan_detail');
            $table->index(['referensi_tipe', 'referensi_id'], 'idx_bil_tagihan_detail_ref');
        });

        Schema::create('bil_pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('bil_tagihan', 'id', 'fk_bil_pembayaran_tagihan');
            $table->foreignId('metode_bayar_id')->constrained('mtr_metode_bayar', 'id', 'fk_bil_pembayaran_metode');
            $table->decimal('jumlah_bayar', 15, 2);
            $table->decimal('kembalian', 15, 2)->default(0);
            $table->string('no_referensi', 100)->nullable();
            $table->foreignId('kasir_id')->constrained('sys_pengguna', 'id', 'fk_bil_pembayaran_kasir');
            $table->timestamp('dibayar_pada')->useCurrent();
            $table->timestamps();

            $table->index(['tagihan_id'], 'idx_bil_pembayaran_tagihan');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('bil_pembayaran');
        Schema::dropIfExists('bil_tagihan_detail');
        Schema::dropIfExists('bil_tagihan');
        Schema::enableForeignKeyConstraints();
    }
};
