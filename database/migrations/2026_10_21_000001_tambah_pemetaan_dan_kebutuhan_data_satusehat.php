<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pemetaan terminologi (KFA/LOINC/SNOMED) dan kebutuhan data tambahan untuk SATUSEHAT.
 * Lihat docs/satusehat-kebutuhan-data.md. Semua kolom baru nullable/berdefault agar tidak
 * mengganggu data yang sudah ada. Aman dijalankan di atas migration tahap 2.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- Master: obat (KFA) ----
        // Catatan: kolom lama `kode_kfa` dipakai sebagai kode produk obat AKTUAL (93xxxxxx).
        Schema::table('mtr_obat', function (Blueprint $table) {
            $table->string('kode_kfa_virtual', 30)->nullable()->after('kode_kfa')
                ->comment('kode produk obat virtual KFA (verifikasi pemakaian)');
            $table->string('nama_kfa', 255)->nullable()->after('kode_kfa_virtual');
            $table->string('kode_bentuk_sediaan', 30)->nullable()->after('nama_kfa');
            $table->string('nama_bentuk_sediaan', 100)->nullable()->after('kode_bentuk_sediaan');
        });

        // ---- Master: lab (LOINC + SNOMED spesimen + UCUM) ----
        Schema::table('mtr_pemeriksaan_lab', function (Blueprint $table) {
            $table->string('kode_loinc', 20)->nullable()->after('nama');
            $table->string('nama_loinc', 255)->nullable()->after('kode_loinc');
            $table->string('kode_spesimen', 30)->nullable()->after('nama_loinc')->comment('SNOMED CT jenis spesimen');
            $table->string('nama_spesimen', 100)->nullable()->after('kode_spesimen');
        });

        Schema::table('mtr_parameter_lab', function (Blueprint $table) {
            $table->string('kode_loinc', 20)->nullable()->after('nama_parameter');
            $table->string('nama_loinc', 255)->nullable()->after('kode_loinc');
            $table->string('satuan_ucum', 30)->nullable()->after('satuan');
            $table->enum('jenis_nilai', ['numerik', 'kualitatif'])->default('numerik')->after('satuan_ucum');
        });

        // ---- Master: poli (Encounter.serviceType) ----
        Schema::table('mtr_poli', function (Blueprint $table) {
            $table->string('kode_tipe_pelayanan', 30)->nullable()->after('nama')
                ->comment('Encounter.serviceType (lampiran terminologi)');
            $table->string('nama_tipe_pelayanan', 100)->nullable()->after('kode_tipe_pelayanan');
        });

        // ---- Master baru: tenaga kesehatan non-dokter & lokasi ----
        Schema::create('mtr_nakes', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->enum('jenis', ['perawat', 'apoteker', 'petugas_lab', 'lainnya']);
            $table->string('nik', 500)->nullable()->comment('terenkripsi (cast encrypted)');
            $table->char('nik_hash', 64)->nullable()->comment('HMAC-SHA256 NIK');
            $table->string('no_str', 50)->nullable();
            $table->string('ihs_practitioner_id', 64)->nullable();
            $table->foreignId('pengguna_id')->nullable()->comment('akun login (opsional)')
                ->constrained('sys_pengguna', 'id', 'fk_mtr_nakes_pengguna');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['nik_hash'], 'uk_mtr_nakes_nik_hash');
            $table->unique(['pengguna_id'], 'uk_mtr_nakes_pengguna');
        });

        Schema::create('mtr_lokasi', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10);
            $table->string('nama', 100);
            $table->string('ihs_location_id', 64)->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['kode'], 'uk_mtr_lokasi_kode');
        });

        // ---- Registrasi: waktu, cara keluar, riwayat status ----
        Schema::table('reg_kunjungan', function (Blueprint $table) {
            $table->timestamp('waktu_datang')->nullable()->after('tanggal_kunjungan');
            $table->timestamp('waktu_mulai')->nullable()->after('waktu_datang');
            $table->timestamp('waktu_selesai')->nullable()->after('waktu_mulai');
            $table->string('kode_cara_keluar', 30)->nullable()->after('status')->comment('kode terminologi cara keluar');
            $table->string('nama_cara_keluar', 100)->nullable()->after('kode_cara_keluar');
        });

        Schema::create('reg_kunjungan_riwayat_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kunjungan_id')->constrained('reg_kunjungan', 'id', 'fk_reg_riwayat_kunjungan');
            $table->string('status', 20);
            $table->timestamp('waktu');
            $table->foreignId('dibuat_oleh')->nullable()->constrained('sys_pengguna', 'id', 'fk_reg_riwayat_oleh');
            $table->timestamps();

            $table->index(['kunjungan_id', 'waktu'], 'idx_reg_riwayat_status');
        });

        // ---- ERM: dosis terstruktur e-resep ----
        Schema::table('erm_resep_item', function (Blueprint $table) {
            $table->decimal('dosis', 10, 3)->nullable()->after('aturan_pakai');
            $table->string('satuan_dosis', 30)->nullable()->after('dosis');
            $table->unsignedSmallInteger('frekuensi')->nullable()->after('satuan_dosis')->comment('jumlah pemberian per periode');
            $table->unsignedSmallInteger('periode')->nullable()->after('frekuensi')->comment('panjang periode');
            $table->enum('satuan_periode', ['jam', 'hari', 'minggu'])->nullable()->after('periode');
            $table->string('kode_rute', 30)->nullable()->after('satuan_periode');
            $table->string('nama_rute', 100)->nullable()->after('kode_rute');
            $table->unsignedSmallInteger('lama_hari')->nullable()->after('nama_rute');
            $table->string('aturan_tambahan', 255)->nullable()->after('lama_hari');
        });

        // ---- Lab: spesimen ----
        Schema::create('lab_spesimen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_id')->constrained('lab_permintaan', 'id', 'fk_lab_spesimen_permintaan')->cascadeOnDelete();
            $table->string('kode_spesimen', 30)->comment('SNOMED CT');
            $table->string('nama_spesimen', 100);
            $table->enum('status', ['menunggu_diambil', 'diambil', 'diterima', 'ditolak'])->default('menunggu_diambil');
            $table->timestamp('waktu_pengambilan')->nullable();
            $table->foreignId('diambil_oleh')->nullable()->constrained('sys_pengguna', 'id', 'fk_lab_spesimen_oleh');
            $table->string('catatan', 255)->nullable();
            $table->timestamps();

            $table->index(['permintaan_id'], 'idx_lab_spesimen_permintaan');
        });

        Schema::table('lab_permintaan_detail', function (Blueprint $table) {
            $table->foreignId('spesimen_id')->nullable()->after('pemeriksaan_lab_id')
                ->constrained('lab_spesimen', 'id', 'fk_lab_perm_det_spesimen');
        });

        // ---- SATUSEHAT: ketergantungan & pembeda resource ----
        Schema::table('sts_sinkronisasi', function (Blueprint $table) {
            $table->enum('aksi', ['POST', 'PUT'])->default('POST')->after('jenis_resource');
            $table->string('sub_kunci', 100)->default('')->after('referensi_id')
                ->comment('pembeda bila satu referensi menghasilkan lebih dari satu resource');
            $table->foreignId('bergantung_pada_id')->nullable()->after('status')
                ->comment('baris sinkronisasi induk yang harus berhasil dulu')
                ->constrained('sts_sinkronisasi', 'id', 'fk_sts_sinkronisasi_induk');

            $table->unique(['jenis_resource', 'aksi', 'referensi_tabel', 'referensi_id', 'sub_kunci'], 'uk_sts_sinkronisasi_ref');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('sts_sinkronisasi', function (Blueprint $table) {
            $table->dropForeign('fk_sts_sinkronisasi_induk');
            $table->dropUnique('uk_sts_sinkronisasi_ref');
            $table->dropColumn(['aksi', 'sub_kunci', 'bergantung_pada_id']);
        });

        Schema::table('lab_permintaan_detail', function (Blueprint $table) {
            $table->dropForeign('fk_lab_perm_det_spesimen');
            $table->dropColumn('spesimen_id');
        });
        Schema::dropIfExists('lab_spesimen');

        Schema::table('erm_resep_item', function (Blueprint $table) {
            $table->dropColumn(['dosis', 'satuan_dosis', 'frekuensi', 'periode', 'satuan_periode',
                'kode_rute', 'nama_rute', 'lama_hari', 'aturan_tambahan']);
        });

        Schema::dropIfExists('reg_kunjungan_riwayat_status');
        Schema::table('reg_kunjungan', function (Blueprint $table) {
            $table->dropColumn(['waktu_datang', 'waktu_mulai', 'waktu_selesai', 'kode_cara_keluar', 'nama_cara_keluar']);
        });

        Schema::dropIfExists('mtr_lokasi');
        Schema::dropIfExists('mtr_nakes');

        Schema::table('mtr_poli', function (Blueprint $table) {
            $table->dropColumn(['kode_tipe_pelayanan', 'nama_tipe_pelayanan']);
        });
        Schema::table('mtr_parameter_lab', function (Blueprint $table) {
            $table->dropColumn(['kode_loinc', 'nama_loinc', 'satuan_ucum', 'jenis_nilai']);
        });
        Schema::table('mtr_pemeriksaan_lab', function (Blueprint $table) {
            $table->dropColumn(['kode_loinc', 'nama_loinc', 'kode_spesimen', 'nama_spesimen']);
        });
        Schema::table('mtr_obat', function (Blueprint $table) {
            $table->dropColumn(['kode_kfa_virtual', 'nama_kfa', 'kode_bentuk_sediaan', 'nama_bentuk_sediaan']);
        });

        Schema::enableForeignKeyConstraints();
    }
};
