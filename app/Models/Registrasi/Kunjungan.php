<?php

namespace App\Models\Registrasi;

use App\Models\Apotek\PenjualanUmum;
use App\Models\Billing\Tagihan;
use App\Models\Erm\Pemeriksaan;
use App\Models\Lab\Permintaan;
use App\Models\Master\Pasien;
use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Kunjungan extends ModelDasar
{
    protected $table = 'reg_kunjungan';

    protected function casts(): array
    {
        return [
            'tanggal_kunjungan' => 'date',
        ];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function ttv(): HasOne
    {
        return $this->hasOne(Ttv::class, 'kunjungan_id');
    }

    public function antrean(): HasMany
    {
        return $this->hasMany(Antrean::class, 'kunjungan_id');
    }

    public function pemeriksaan(): HasMany
    {
        return $this->hasMany(Pemeriksaan::class, 'kunjungan_id');
    }

    public function permintaan(): HasMany
    {
        return $this->hasMany(Permintaan::class, 'kunjungan_id');
    }

    public function penjualanUmum(): HasMany
    {
        return $this->hasMany(PenjualanUmum::class, 'kunjungan_id');
    }

    public function tagihan(): HasOne
    {
        return $this->hasOne(Tagihan::class, 'kunjungan_id');
    }
}
