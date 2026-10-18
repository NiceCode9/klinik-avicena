<?php

namespace App\Models\Billing;

use App\Models\Master\MetodeBayar;
use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends ModelDasar
{
    protected $table = 'bil_pembayaran';

    protected function casts(): array
    {
        return [
            'jumlah_bayar' => 'decimal:2',
            'kembalian' => 'decimal:2',
            'dibayar_pada' => 'datetime',
        ];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }

    public function metodeBayar(): BelongsTo
    {
        return $this->belongsTo(MetodeBayar::class, 'metode_bayar_id');
    }

    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }
}
