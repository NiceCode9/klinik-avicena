<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul erm_ (Erm) - 11 tabel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erm_pemeriksaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->constrained('reg_kunjungan', 'id', 'fk_erm_pemeriksaan_kunjungan');
            $table->foreignId('antrean_id')->nullable()->constrained('reg_antrean', 'id', 'fk_erm_pemeriksaan_antrean');
            $table->foreignId('poli_id')->constrained('mtr_poli', 'id', 'fk_erm_pemeriksaan_poli');
            $table->foreignId('dokter_id')->constrained('mtr_dokter', 'id', 'fk_erm_pemeriksaan_dokter');
            $table->text('subjektif')->nullable();
            $table->text('objektif')->nullable();
            $table->text('asesmen')->nullable();
            $table->text('rencana')->nullable();
            $table->boolean('dikunci')->default(false)->comment('dikunci setelah kasir konfirmasi bayar');
            $table->timestamp('dikunci_pada')->nullable();
            $table->timestamps();

            $table->index(['kunjungan_id'], 'idx_erm_pemeriksaan_kunjungan');
            $table->index(['dokter_id'], 'idx_erm_pemeriksaan_dokter');
        });

        Schema::create('erm_diagnosis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('erm_pemeriksaan', 'id', 'fk_erm_diagnosis_pemeriksaan');
            $table->foreignId('icd10_id')->constrained('mtr_icd10', 'id', 'fk_erm_diagnosis_icd10');
            $table->enum('jenis', ['primer', 'sekunder'])->default('primer');
            $table->string('ihs_condition_id', 64)->nullable();
            $table->timestamps();

            $table->index(['pemeriksaan_id'], 'idx_erm_diagnosis_pemeriksaan');
        });

        Schema::create('erm_detail_penyakit_dalam', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('erm_pemeriksaan', 'id', 'fk_erm_detail_pd');
            $table->text('riwayat_penyakit_kronis')->nullable();
            $table->text('status_metabolik')->nullable();
            $table->json('review_sistem_organ')->nullable();
            $table->timestamps();

            $table->unique(['pemeriksaan_id'], 'uk_erm_detail_pd');
        });

        Schema::create('erm_detail_saraf', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('erm_pemeriksaan', 'id', 'fk_erm_detail_saraf');
            $table->json('evaluasi_motorik')->nullable();
            $table->json('evaluasi_sensorik')->nullable();
            $table->json('saraf_kranial')->nullable()->comment('N I - N XII');
            $table->timestamps();

            $table->unique(['pemeriksaan_id'], 'uk_erm_detail_saraf');
        });

        Schema::create('erm_odontogram', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('erm_pemeriksaan', 'id', 'fk_erm_odontogram_pem');
            $table->string('nomor_gigi', 3)->comment('notasi FDI, mis. 11, 48, 55');
            $table->set('permukaan', ['mesial', 'distal', 'oklusal', 'bukal', 'lingual', 'palatal', 'seluruh'])->nullable();
            $table->string('kondisi', 50)->comment('karies, tambalan, hilang, mahkota, dll');
            $table->string('tindakan', 100)->nullable();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index(['pemeriksaan_id', 'nomor_gigi'], 'idx_erm_odontogram_pem');
        });

        Schema::create('erm_tindakan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('erm_pemeriksaan', 'id', 'fk_erm_tindakan_pem');
            $table->foreignId('tindakan_id')->constrained('mtr_tindakan', 'id', 'fk_erm_tindakan_tindakan');
            $table->unsignedInteger('jumlah')->default(1);
            $table->decimal('tarif', 15, 2)->default(0)->comment('snapshot tarif saat tindakan');
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index(['pemeriksaan_id'], 'idx_erm_tindakan_pem');
        });

        Schema::create('erm_resep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('erm_pemeriksaan', 'id', 'fk_erm_resep_pemeriksaan');
            $table->foreignId('dokter_id')->constrained('mtr_dokter', 'id', 'fk_erm_resep_dokter');
            $table->string('no_resep', 30);
            $table->enum('status', ['diterbitkan', 'divalidasi', 'diracik', 'diserahkan', 'batal'])->default('diterbitkan');
            $table->foreignId('apoteker_id')->nullable()->constrained('sys_pengguna', 'id', 'fk_erm_resep_apoteker');
            $table->timestamp('divalidasi_pada')->nullable();
            $table->timestamp('diserahkan_pada')->nullable();
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->unique(['no_resep'], 'uk_erm_resep_no');
            $table->index(['pemeriksaan_id'], 'idx_erm_resep_pemeriksaan');
            $table->index(['status'], 'idx_erm_resep_status');
        });

        Schema::create('erm_resep_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resep_id')->constrained('erm_resep', 'id', 'fk_erm_resep_item_resep')->cascadeOnDelete();
            $table->enum('jenis', ['reguler', 'racikan'])->default('reguler');
            $table->foreignId('obat_id')->nullable()->comment('wajib jika jenis = reguler')->constrained('mtr_obat', 'id', 'fk_erm_resep_item_obat');
            $table->foreignId('racikan_id')->nullable()->comment('template racikan (opsional)')->constrained('mtr_racikan', 'id', 'fk_erm_resep_item_racikan');
            $table->string('nama_racikan', 150)->nullable();
            $table->decimal('jumlah', 12, 2)->comment('reguler: qty obat; racikan: jumlah bungkus/kapsul');
            $table->string('aturan_pakai', 255)->nullable();
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('biaya_jasa_racik', 15, 2)->default(0)->comment('flat per resep racikan');
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index(['resep_id'], 'idx_erm_resep_item_resep');
        });

        Schema::create('erm_resep_racikan_bahan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resep_item_id')->constrained('erm_resep_item', 'id', 'fk_erm_racikan_bahan_item')->cascadeOnDelete();
            $table->foreignId('obat_id')->constrained('mtr_obat', 'id', 'fk_erm_racikan_bahan_obat');
            $table->decimal('jumlah_bahan', 12, 3);
            $table->foreignId('satuan_id')->constrained('mtr_satuan', 'id', 'fk_erm_racikan_bahan_satuan');
            $table->timestamps();

            $table->index(['resep_item_id'], 'idx_erm_racikan_bahan_item');
        });

        Schema::create('erm_rujukan_lab', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('erm_pemeriksaan', 'id', 'fk_erm_rujukan_lab_pem');
            $table->foreignId('dokter_id')->constrained('mtr_dokter', 'id', 'fk_erm_rujukan_lab_dokter');
            $table->text('catatan_klinis')->nullable();
            $table->enum('status', ['diminta', 'diproses', 'selesai', 'batal'])->default('diminta');
            $table->timestamps();

            $table->index(['pemeriksaan_id'], 'idx_erm_rujukan_lab_pem');
        });

        Schema::create('erm_rujukan_lab_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rujukan_lab_id')->constrained('erm_rujukan_lab', 'id', 'fk_erm_rujukan_det_rujukan')->cascadeOnDelete();
            $table->foreignId('pemeriksaan_lab_id')->constrained('mtr_pemeriksaan_lab', 'id', 'fk_erm_rujukan_det_lab');
            $table->timestamps();

            $table->unique(['rujukan_lab_id', 'pemeriksaan_lab_id'], 'uk_erm_rujukan_lab_detail');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('erm_rujukan_lab_detail');
        Schema::dropIfExists('erm_rujukan_lab');
        Schema::dropIfExists('erm_resep_racikan_bahan');
        Schema::dropIfExists('erm_resep_item');
        Schema::dropIfExists('erm_resep');
        Schema::dropIfExists('erm_tindakan');
        Schema::dropIfExists('erm_odontogram');
        Schema::dropIfExists('erm_detail_saraf');
        Schema::dropIfExists('erm_detail_penyakit_dalam');
        Schema::dropIfExists('erm_diagnosis');
        Schema::dropIfExists('erm_pemeriksaan');
        Schema::enableForeignKeyConstraints();
    }
};
