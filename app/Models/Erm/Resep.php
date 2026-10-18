<?php

namespace App\Models\Erm;

use App\Models\Apotek\ReturPasien;
use App\Models\Master\Dokter;
use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resep extends ModelDasar
{
    protected $table = 'erm_resep';

    protected function casts(): array
    {
        return [
            'divalidasi_pada' => 'datetime',
            'diserahkan_pada' => 'datetime',
        ];
    }

    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(Pemeriksaan::class, 'pemeriksaan_id');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function apoteker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'apoteker_id');
    }

    public function resepItem(): HasMany
    {
        return $this->hasMany(ResepItem::class, 'resep_id');
    }

    public function returPasien(): HasMany
    {
        return $this->hasMany(ReturPasien::class, 'resep_id');
    }
}
