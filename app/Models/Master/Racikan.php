<?php

namespace App\Models\Master;

use App\Models\Erm\ResepItem;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Racikan extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_racikan';

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function racikanBahan(): HasMany
    {
        return $this->hasMany(RacikanBahan::class, 'racikan_id');
    }

    public function resepItem(): HasMany
    {
        return $this->hasMany(ResepItem::class, 'racikan_id');
    }
}
