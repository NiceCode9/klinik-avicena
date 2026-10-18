<?php

namespace App\Models\Master;

use App\Models\Erm\ResepRacikanBahan;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Satuan extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_satuan';

    public function obat(): HasMany
    {
        return $this->hasMany(Obat::class, 'satuan_id');
    }

    public function racikanBahan(): HasMany
    {
        return $this->hasMany(RacikanBahan::class, 'satuan_id');
    }

    public function resepRacikanBahan(): HasMany
    {
        return $this->hasMany(ResepRacikanBahan::class, 'satuan_id');
    }
}
