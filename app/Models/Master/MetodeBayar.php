<?php

namespace App\Models\Master;

use App\Models\Billing\Pembayaran;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetodeBayar extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_metode_bayar';

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'metode_bayar_id');
    }
}
