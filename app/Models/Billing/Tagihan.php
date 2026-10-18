<?php

namespace App\Models\Billing;

use App\Models\Apotek\PenjualanUmum;
use App\Models\ModelDasar;
use App\Models\Registrasi\Kunjungan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tagihan extends ModelDasar
{
    protected $table = 'bil_tagihan';

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'diskon' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function penjualanUmum(): BelongsTo
    {
        return $this->belongsTo(PenjualanUmum::class, 'penjualan_umum_id');
    }

    public function tagihanDetail(): HasMany
    {
        return $this->hasMany(TagihanDetail::class, 'tagihan_id');
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class, 'tagihan_id');
    }
}
