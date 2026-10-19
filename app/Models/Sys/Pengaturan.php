<?php

namespace App\Models\Sys;

use App\Models\ModelDasar;

class Pengaturan extends ModelDasar
{
    protected $table = 'sys_pengaturan';

    /**
     * Jenis input per kunci; kunci di luar peta ini diperlakukan sebagai teks.
     */
    public const TIPE = [
        'biaya_jasa_racik' => 'uang',
        'retur_pasien_diizinkan' => 'boolean',
        'batas_peringatan_kedaluwarsa_hari' => 'angka',
    ];
}
