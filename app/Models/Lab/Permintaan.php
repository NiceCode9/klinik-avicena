<?php

namespace App\Models\Lab;

use App\Models\Erm\RujukanLab;
use App\Models\Master\Dokter;
use App\Models\ModelDasar;
use App\Models\Registrasi\Antrean;
use App\Models\Registrasi\Kunjungan;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permintaan extends ModelDasar
{
    protected $table = 'lab_permintaan';

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

    public function rujukanLab(): BelongsTo
    {
        return $this->belongsTo(RujukanLab::class, 'rujukan_lab_id');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function permintaanDetail(): HasMany
    {
        return $this->hasMany(PermintaanDetail::class, 'permintaan_id');
    }
}
