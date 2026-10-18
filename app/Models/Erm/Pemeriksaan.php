<?php

namespace App\Models\Erm;

use App\Models\Master\Dokter;
use App\Models\Master\Poli;
use App\Models\ModelDasar;
use App\Models\Registrasi\Antrean;
use App\Models\Registrasi\Kunjungan;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pemeriksaan extends ModelDasar
{
    protected $table = 'erm_pemeriksaan';

    protected function casts(): array
    {
        return [
            'dikunci' => 'boolean',
            'dikunci_pada' => 'datetime',
        ];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function antrean(): BelongsTo
    {
        return $this->belongsTo(Antrean::class, 'antrean_id');
    }

    public function poli(): BelongsTo
    {
        return $this->belongsTo(Poli::class, 'poli_id');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function diagnosis(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'pemeriksaan_id');
    }

    public function detailPenyakitDalam(): HasOne
    {
        return $this->hasOne(DetailPenyakitDalam::class, 'pemeriksaan_id');
    }

    public function detailSaraf(): HasOne
    {
        return $this->hasOne(DetailSaraf::class, 'pemeriksaan_id');
    }

    public function odontogram(): HasMany
    {
        return $this->hasMany(Odontogram::class, 'pemeriksaan_id');
    }

    public function tindakan(): HasMany
    {
        return $this->hasMany(Tindakan::class, 'pemeriksaan_id');
    }

    public function resep(): HasMany
    {
        return $this->hasMany(Resep::class, 'pemeriksaan_id');
    }

    public function rujukanLab(): HasMany
    {
        return $this->hasMany(RujukanLab::class, 'pemeriksaan_id');
    }
}
