<?php

namespace App\Models\SatuSehat;

use App\Models\ModelDasar;

class Token extends ModelDasar
{
    protected $table = 'sts_token';

    protected function casts(): array
    {
        return [
            'token' => 'encrypted',
            'kedaluwarsa_pada' => 'datetime',
        ];
    }
}
