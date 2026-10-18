<?php

namespace App\Models\Lab;

use App\Models\Master\PemeriksaanLab;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanDetail extends ModelDasar
{
    protected $table = 'lab_permintaan_detail';

    protected function casts(): array
    {
        return [
            'tarif' => 'decimal:2',
        ];
    }

    public function permintaan(): BelongsTo
    {
        return $this->belongsTo(Permintaan::class, 'permintaan_id');
    }

    public function pemeriksaanLab(): BelongsTo
    {
        return $this->belongsTo(PemeriksaanLab::class, 'pemeriksaan_lab_id');
    }

    public function hasil(): HasMany
    {
        return $this->hasMany(Hasil::class, 'permintaan_detail_id');
    }
}
