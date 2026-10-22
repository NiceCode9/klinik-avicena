<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mtr_satuan', function (Blueprint $table) {
            $table->string('kode_ucum', 30)->nullable()->after('nama')
                ->comment('kode UCUM untuk satuan dosis/lab (D25)');
        });
    }

    public function down(): void
    {
        Schema::table('mtr_satuan', function (Blueprint $table) {
            $table->dropColumn('kode_ucum');
        });
    }
};
