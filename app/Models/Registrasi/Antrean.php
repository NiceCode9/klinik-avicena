<?php

namespace App\Models\Registrasi;

use App\Models\Erm\Pemeriksaan;
use App\Models\Lab\Permintaan;
use App\Models\Master\Poli;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Antrean extends ModelDasar
{
    protected $table = 'reg_antrean';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'waktu_panggil' => 'datetime',
            'waktu_selesai' => 'datetime',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class, 'poli_id');
    }

    public function pemeriksaan(): HasMany
    {
        return $this->hasMany(Pemeriksaan::class, 'antrean_id');
    }

    public function permintaan(): HasMany
    {
        return $this->hasMany(Permintaan::class, 'antrean_id');
    }
}
