<?php

namespace App\Models\Apotek;

use App\Models\Billing\Tagihan;
use App\Models\Master\Pasien;
use App\Models\ModelDasar;
use App\Models\Registrasi\Kunjungan;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PenjualanUmum extends ModelDasar
{
    protected $table = 'apt_penjualan_umum';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total' => 'decimal:2',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function penjualanUmumDetail(): HasMany
    {
        return $this->hasMany(PenjualanUmumDetail::class, 'penjualan_id');
    }

    public function returPasien(): HasMany
    {
        return $this->hasMany(ReturPasien::class, 'penjualan_id');
    }

    public function tagihan(): HasOne
    {
        return $this->hasOne(Tagihan::class, 'penjualan_umum_id');
    }
}
