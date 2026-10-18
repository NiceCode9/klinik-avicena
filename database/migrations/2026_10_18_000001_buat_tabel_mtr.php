<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul mtr_ (Master) - 14 tabel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mtr_poli', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 5)->comment('INT, GIG, SRF, LAB');
            $table->string('nama', 100);
            $table->string('ihs_location_id', 64)->nullable()->comment('ID Location SATUSEHAT');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kode'], 'uk_mtr_poli_kode');
        });

        Schema::create('mtr_dokter', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->string('spesialisasi', 100)->nullable();
            $table->foreignId('poli_id')->nullable()->constrained('mtr_poli', 'id', 'fk_mtr_dokter_poli');
            $table->string('no_sip', 50)->nullable();
            $table->string('nik', 500)->nullable()->comment('terenkripsi (cast encrypted)');
            $table->string('ihs_practitioner_id', 64)->nullable();
            $table->decimal('tarif_konsultasi', 15, 2)->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['poli_id'], 'idx_mtr_dokter_poli');
        });

        Schema::create('mtr_pasien', function (Blueprint $table) {
            $table->id();
            $table->string('no_rekam_medis', 20);
            $table->string('nik', 500)->comment('terenkripsi (cast encrypted)');
            $table->char('nik_hash', 64)->comment('HMAC-SHA256 NIK untuk pencarian');
            $table->string('nama_lengkap', 150);
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->enum('golongan_darah', ['A', 'B', 'AB', 'O', '-'])->default('-');
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->string('nama_wali', 150)->nullable();
            $table->string('ihs_number', 64)->nullable()->comment('IHS Number dari SATUSEHAT');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['no_rekam_medis'], 'uk_mtr_pasien_rm');
            $table->unique(['nik_hash'], 'uk_mtr_pasien_nik_hash');
            $table->index(['nama_lengkap'], 'idx_mtr_pasien_nama');
        });

        Schema::create('mtr_icd10', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10);
            $table->string('nama_diagnosis', 255);
            $table->timestamps();

            $table->unique(['kode'], 'uk_mtr_icd10_kode');
            $table->index(['nama_diagnosis'], 'idx_mtr_icd10_nama');
        });

        Schema::create('mtr_tindakan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20);
            $table->string('nama', 150);
            $table->foreignId('poli_id')->nullable()->constrained('mtr_poli', 'id', 'fk_mtr_tindakan_poli');
            $table->decimal('tarif', 15, 2)->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kode'], 'uk_mtr_tindakan_kode');
        });

        Schema::create('mtr_kategori_obat', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['nama'], 'uk_mtr_kategori_obat_nama');
        });

        Schema::create('mtr_satuan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['nama'], 'uk_mtr_satuan_nama');
        });

        Schema::create('mtr_obat', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30);
            $table->string('nama', 200);
            $table->foreignId('kategori_obat_id')->nullable()->constrained('mtr_kategori_obat', 'id', 'fk_mtr_obat_kategori');
            $table->foreignId('satuan_id')->constrained('mtr_satuan', 'id', 'fk_mtr_obat_satuan');
            $table->enum('golongan', ['bebas', 'bebas_terbatas', 'keras', 'narkotika', 'psikotropika'])->default('bebas');
            $table->decimal('stok_minimum', 12, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->string('kode_kfa', 50)->nullable()->comment('kode KFA untuk SATUSEHAT (opsional)');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kode'], 'uk_mtr_obat_kode');
            $table->index(['nama'], 'idx_mtr_obat_nama');
        });

        Schema::create('mtr_supplier', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->string('nama_kontak', 100)->nullable();
            $table->string('no_izin_pbf', 50)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mtr_pemeriksaan_lab', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20);
            $table->string('nama', 150);
            $table->decimal('tarif', 15, 2)->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kode'], 'uk_mtr_pemeriksaan_lab_kode');
        });

        Schema::create('mtr_parameter_lab', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_lab_id')->constrained('mtr_pemeriksaan_lab', 'id', 'fk_mtr_parameter_lab_pem');
            $table->string('nama_parameter', 150);
            $table->string('satuan', 50)->nullable();
            $table->decimal('nilai_rujukan_min', 12, 3)->nullable();
            $table->decimal('nilai_rujukan_max', 12, 3)->nullable();
            $table->string('keterangan_rujukan', 255)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pemeriksaan_lab_id'], 'idx_mtr_parameter_lab_pem');
        });

        Schema::create('mtr_racikan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_menu', 150);
            $table->string('keterangan', 255)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('mtr_racikan_bahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('racikan_id')->constrained('mtr_racikan', 'id', 'fk_mtr_racikan_bahan_racikan')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('mtr_obat', 'id', 'fk_mtr_racikan_bahan_obat');
            $table->decimal('jumlah', 12, 3);
            $table->foreignId('satuan_id')->constrained('mtr_satuan', 'id', 'fk_mtr_racikan_bahan_satuan');
            $table->timestamps();

            $table->unique(['racikan_id', 'obat_id'], 'uk_mtr_racikan_bahan');
        });

        Schema::create('mtr_metode_bayar', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['nama'], 'uk_mtr_metode_bayar_nama');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('mtr_metode_bayar');
        Schema::dropIfExists('mtr_racikan_bahan');
        Schema::dropIfExists('mtr_racikan');
        Schema::dropIfExists('mtr_parameter_lab');
        Schema::dropIfExists('mtr_pemeriksaan_lab');
        Schema::dropIfExists('mtr_supplier');
        Schema::dropIfExists('mtr_obat');
        Schema::dropIfExists('mtr_satuan');
        Schema::dropIfExists('mtr_kategori_obat');
        Schema::dropIfExists('mtr_tindakan');
        Schema::dropIfExists('mtr_icd10');
        Schema::dropIfExists('mtr_pasien');
        Schema::dropIfExists('mtr_dokter');
        Schema::dropIfExists('mtr_poli');
        Schema::enableForeignKeyConstraints();
    }
};
