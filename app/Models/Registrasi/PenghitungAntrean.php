<?php

namespace App\Models\Registrasi;

use App\Models\Master\Poli;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenghitungAntrean extends ModelDasar
{
    protected $table = 'reg_penghitung_antrean';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class, 'poli_id');
    }
}
