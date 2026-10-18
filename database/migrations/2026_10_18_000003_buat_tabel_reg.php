<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul reg_ (Registrasi) - 4 tabel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reg_kunjungan', function (Blueprint $table) {
            $table->id();
            $table->string('no_kunjungan', 30);
            $table->foreignId('pasien_id')->constrained('mtr_pasien', 'id', 'fk_reg_kunjungan_pasien');
            $table->date('tanggal_kunjungan');
            $table->enum('jenis_kunjungan', ['poli', 'lab_mandiri', 'apotek_umum'])->default('poli');
            $table->enum('status', ['terdaftar', 'ttv', 'menunggu', 'diperiksa', 'lab', 'farmasi', 'kasir', 'selesai', 'batal'])->default('terdaftar');
            $table->string('ihs_encounter_id', 64)->nullable();
            $table->foreignId('dibuat_oleh')->nullable()->constrained('sys_pengguna', 'id', 'fk_reg_kunjungan_oleh');
            $table->timestamps();

            $table->unique(['no_kunjungan'], 'uk_reg_kunjungan_no');
            $table->index(['pasien_id'], 'idx_reg_kunjungan_pasien');
            $table->index(['tanggal_kunjungan', 'status'], 'idx_reg_kunjungan_tgl_status');
        });

        Schema::create('reg_ttv', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->constrained('reg_kunjungan', 'id', 'fk_reg_ttv_kunjungan');
            $table->unsignedSmallInteger('tekanan_darah_sistolik')->nullable();
            $table->unsignedSmallInteger('tekanan_darah_diastolik')->nullable();
            $table->unsignedSmallInteger('nadi')->nullable()->comment('x/menit');
            $table->decimal('suhu', 4, 1)->nullable()->comment('derajat Celsius');
            $table->decimal('berat_badan', 5, 2)->nullable()->comment('kg');
            $table->decimal('tinggi_badan', 5, 2)->nullable()->comment('cm');
            $table->unsignedTinyInteger('saturasi_oksigen')->nullable()->comment('persen');
            $table->foreignId('perawat_id')->nullable()->constrained('sys_pengguna', 'id', 'fk_reg_ttv_perawat');
            $table->timestamps();

            $table->unique(['kunjungan_id'], 'uk_reg_ttv_kunjungan');
        });

        Schema::create('reg_penghitung_antrean', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poli_id')->constrained('mtr_poli', 'id', 'fk_reg_penghitung_poli');
            $table->date('tanggal');
            $table->unsignedInteger('nomor_terakhir')->default(0);
            $table->timestamps();

            $table->unique(['poli_id', 'tanggal'], 'uk_reg_penghitung');
        });

        Schema::create('reg_antrean', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->constrained('reg_kunjungan', 'id', 'fk_reg_antrean_kunjungan');
            $table->foreignId('poli_id')->constrained('mtr_poli', 'id', 'fk_reg_antrean_poli');
            $table->date('tanggal');
            $table->unsignedInteger('nomor_urut');
            $table->string('nomor_antrean', 10)->comment('contoh INT-001');
            $table->enum('status', ['menunggu', 'dipanggil', 'dilayani', 'selesai', 'batal'])->default('menunggu');
            $table->timestamp('waktu_panggil')->nullable();
            $table->timestamp('waktu_selesai')->nullable();
            $table->timestamps();

            $table->unique(['poli_id', 'tanggal', 'nomor_urut'], 'uk_reg_antrean_urut');
            $table->index(['kunjungan_id'], 'idx_reg_antrean_kunjungan');
            $table->index(['poli_id', 'tanggal', 'status'], 'idx_reg_antrean_status');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('reg_antrean');
        Schema::dropIfExists('reg_penghitung_antrean');
        Schema::dropIfExists('reg_ttv');
        Schema::dropIfExists('reg_kunjungan');
        Schema::enableForeignKeyConstraints();
    }
};
