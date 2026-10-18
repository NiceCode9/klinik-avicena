<?php

namespace App\Models\Master;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class KategoriObat extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_kategori_obat';

    public function obat(): HasMany
    {
        return $this->hasMany(Obat::class, 'kategori_obat_id');
    }
}
