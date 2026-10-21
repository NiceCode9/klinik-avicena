<?php

namespace App\Models\Master;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lokasi extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_lokasi';

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }
}
