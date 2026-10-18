<?php

namespace App\Models\SatuSehat;

use App\Models\ModelDasar;

class Sinkronisasi extends ModelDasar
{
    protected $table = 'sts_sinkronisasi';

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'respons' => 'array',
            'terkirim_pada' => 'datetime',
        ];
    }
}
