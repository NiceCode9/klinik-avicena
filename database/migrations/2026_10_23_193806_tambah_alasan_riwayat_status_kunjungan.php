<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reg_kunjungan_riwayat_status', function (Blueprint $table) {
            $table->text('alasan')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('reg_kunjungan_riwayat_status', function (Blueprint $table) {
            $table->dropColumn('alasan');
        });
    }
};
