<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul sts_ (SatuSehat) - 2 tabel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sts_sinkronisasi', function (Blueprint $table) {
            $table->id();
            $table->string('jenis_resource', 50)->comment('Encounter, Condition, dst');
            $table->string('referensi_tabel', 100);
            $table->unsignedBigInteger('referensi_id');
            $table->enum('status', ['menunggu', 'berhasil', 'gagal'])->default('menunggu');
            $table->string('ihs_id', 64)->nullable();
            $table->unsignedTinyInteger('jumlah_percobaan')->default(0);
            $table->json('payload')->nullable();
            $table->json('respons')->nullable();
            $table->text('pesan_galat')->nullable();
            $table->timestamp('terkirim_pada')->nullable();
            $table->timestamps();

            $table->index(['referensi_tabel', 'referensi_id'], 'idx_sts_sinkronisasi_ref');
            $table->index(['status', 'jenis_resource'], 'idx_sts_sinkronisasi_status');
        });

        Schema::create('sts_token', function (Blueprint $table) {
            $table->id();
            $table->text('token')->comment('terenkripsi (cast encrypted)');
            $table->timestamp('kedaluwarsa_pada');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('sts_token');
        Schema::dropIfExists('sts_sinkronisasi');
        Schema::enableForeignKeyConstraints();
    }
};
