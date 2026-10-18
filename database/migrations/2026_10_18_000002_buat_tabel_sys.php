<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul sys_ (Sys) - 6 tabel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sys_peran', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique(['nama'], 'uk_sys_peran_nama');
        });

        Schema::create('sys_pengguna', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('username');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('peran_id')->constrained('sys_peran', 'id', 'fk_sys_pengguna_peran');
            $table->foreignId('dokter_id')->nullable()->comment('terisi jika pengguna adalah dokter')->constrained('mtr_dokter', 'id', 'fk_sys_pengguna_dokter');
            $table->boolean('aktif')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['username'], 'uk_sys_pengguna_username');
            $table->unique(['email'], 'uk_sys_pengguna_email');
            $table->index(['peran_id'], 'idx_sys_pengguna_peran');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('sys_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('induk_id')->nullable()->comment('menu grup (induk); NULL untuk tingkat teratas')->constrained('sys_menu', 'id', 'fk_sys_menu_induk');
            $table->string('kode', 50)->comment('kontrak dengan kode aplikasi, mis. pendaftaran.pasien');
            $table->string('nama', 100);
            $table->string('ikon', 50)->nullable();
            $table->string('rute', 100)->nullable()->comment('nama route; NULL untuk menu grup');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->unique(['kode'], 'uk_sys_menu_kode');
        });

        Schema::create('sys_hak_akses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peran_id')->constrained('sys_peran', 'id', 'fk_sys_hak_akses_peran');
            $table->foreignId('menu_id')->constrained('sys_menu', 'id', 'fk_sys_hak_akses_menu')->cascadeOnDelete();
            $table->boolean('bisa_lihat')->default(false);
            $table->boolean('bisa_tambah')->default(false);
            $table->boolean('bisa_ubah')->default(false);
            $table->boolean('bisa_hapus')->default(false);
            $table->timestamps();

            $table->unique(['peran_id', 'menu_id'], 'uk_sys_hak_akses');
        });

        Schema::create('sys_log_aktivitas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pengguna_id')->nullable()->constrained('sys_pengguna', 'id', 'fk_sys_log_pengguna');
            $table->string('aksi', 50)->comment('tambah, ubah, hapus, lihat, login, dll');
            $table->string('nama_tabel', 100)->nullable();
            $table->unsignedBigInteger('data_id')->nullable();
            $table->json('nilai_lama')->nullable();
            $table->json('nilai_baru')->nullable();
            $table->string('alamat_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['pengguna_id'], 'idx_sys_log_pengguna');
            $table->index(['nama_tabel', 'data_id'], 'idx_sys_log_tabel');
        });

        Schema::create('sys_pengaturan', function (Blueprint $table) {
            $table->id();
            $table->string('kunci', 100);
            $table->text('nilai')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique(['kunci'], 'uk_sys_pengaturan_kunci');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('sys_pengaturan');
        Schema::dropIfExists('sys_log_aktivitas');
        Schema::dropIfExists('sys_hak_akses');
        Schema::dropIfExists('sys_menu');
        Schema::dropIfExists('sys_pengguna');
        Schema::dropIfExists('sys_peran');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        Schema::enableForeignKeyConstraints();
    }
};
