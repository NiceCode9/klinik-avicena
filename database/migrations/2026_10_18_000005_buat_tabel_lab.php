<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul lab_ (Lab) - 3 tabel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_permintaan', function (Blueprint $table) {
            $table->id();
            $table->string('no_permintaan', 30);
            $table->foreignId('kunjungan_id')->constrained('reg_kunjungan', 'id', 'fk_lab_permintaan_kunjungan');
            $table->foreignId('antrean_id')->nullable()->constrained('reg_antrean', 'id', 'fk_lab_permintaan_antrean');
            $table->enum('sumber', ['rujukan', 'mandiri']);
            $table->foreignId('rujukan_lab_id')->nullable()->comment('terisi jika sumber = rujukan')->constrained('erm_rujukan_lab', 'id', 'fk_lab_permintaan_rujukan');
            $table->foreignId('dokter_id')->nullable()->comment('dokter lab penanggung jawab, dipilih petugas lab')->constrained('mtr_dokter', 'id', 'fk_lab_permintaan_dokter');
            $table->foreignId('petugas_id')->nullable()->constrained('sys_pengguna', 'id', 'fk_lab_permintaan_petugas');
            $table->enum('status', ['menunggu', 'diproses', 'selesai', 'batal'])->default('menunggu');
            $table->boolean('dikunci')->default(false)->comment('hasil dikunci lalu tagihan dikirim ke kasir');
            $table->timestamp('dikunci_pada')->nullable();
            $table->timestamps();

            $table->unique(['no_permintaan'], 'uk_lab_permintaan_no');
            $table->index(['kunjungan_id'], 'idx_lab_permintaan_kunjungan');
        });

        Schema::create('lab_permintaan_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_id')->constrained('lab_permintaan', 'id', 'fk_lab_perm_det_perm')->cascadeOnDelete();
            $table->foreignId('pemeriksaan_lab_id')->constrained('mtr_pemeriksaan_lab', 'id', 'fk_lab_perm_det_lab');
            $table->decimal('tarif', 15, 2)->default(0)->comment('snapshot tarif');
            $table->timestamps();

            $table->unique(['permintaan_id', 'pemeriksaan_lab_id'], 'uk_lab_permintaan_detail');
        });

        Schema::create('lab_hasil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_detail_id')->constrained('lab_permintaan_detail', 'id', 'fk_lab_hasil_detail')->cascadeOnDelete();
            $table->foreignId('parameter_lab_id')->constrained('mtr_parameter_lab', 'id', 'fk_lab_hasil_parameter');
            $table->string('nilai', 100);
            $table->string('satuan', 50)->nullable();
            $table->string('nilai_rujukan', 100)->nullable()->comment('snapshot nilai rujukan');
            $table->enum('penanda', ['normal', 'rendah', 'tinggi', 'kritis'])->nullable();
            $table->foreignId('diinput_oleh')->nullable()->constrained('sys_pengguna', 'id', 'fk_lab_hasil_petugas');
            $table->timestamps();

            $table->unique(['permintaan_detail_id', 'parameter_lab_id'], 'uk_lab_hasil');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('lab_hasil');
        Schema::dropIfExists('lab_permintaan_detail');
        Schema::dropIfExists('lab_permintaan');
        Schema::enableForeignKeyConstraints();
    }
};
