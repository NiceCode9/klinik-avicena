<?php

namespace App\Models\Master;

use App\Models\Erm\Pemeriksaan;
use App\Models\Erm\Resep;
use App\Models\Erm\RujukanLab;
use App\Models\Lab\Permintaan;
use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dokter extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_dokter';

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'tarif_konsultasi' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class, 'poli_id');
    }

    public function pengguna(): HasMany
    {
        return $this->hasMany(User::class, 'dokter_id');
    }

    public function pemeriksaan(): HasMany
    {
        return $this->hasMany(Pemeriksaan::class, 'dokter_id');
    }

    public function resep(): HasMany
    {
        return $this->hasMany(Resep::class, 'dokter_id');
    }

    public function rujukanLab(): HasMany
    {
        return $this->hasMany(RujukanLab::class, 'dokter_id');
    }

    public function permintaan(): HasMany
    {
        return $this->hasMany(Permintaan::class, 'dokter_id');
    }
}
