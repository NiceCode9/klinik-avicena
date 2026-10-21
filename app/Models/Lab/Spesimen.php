<?php

namespace App\Models\Lab;

use App\Models\ModelDasar;
use App\Models\Sys\Pengguna;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Spesimen extends ModelDasar
{
    protected $table = 'lab_spesimen';

    protected function casts(): array
    {
        return ['waktu_pengambilan' => 'datetime'];
    }

    public function permintaan(): BelongsTo
    {
        return $this->belongsTo(Permintaan::class, 'permintaan_id');
    }

    public function pengambil(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'diambil_oleh');
    }

    public function permintaanDetail(): HasMany
    {
        return $this->hasMany(PermintaanDetail::class, 'spesimen_id');
    }
}
