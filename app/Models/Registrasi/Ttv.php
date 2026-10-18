<?php

namespace App\Models\Registrasi;

use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ttv extends ModelDasar
{
    protected $table = 'reg_ttv';

    protected function casts(): array
    {
        return [
            'suhu' => 'decimal:1',
            'berat_badan' => 'decimal:2',
            'tinggi_badan' => 'decimal:2',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function perawat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'perawat_id');
    }
}
