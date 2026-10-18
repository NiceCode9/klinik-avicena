<?php

namespace App\Models\Erm;

use App\Models\Lab\Permintaan;
use App\Models\Master\Dokter;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RujukanLab extends ModelDasar
{
    protected $table = 'erm_rujukan_lab';

    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(Pemeriksaan::class, 'pemeriksaan_id');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function rujukanLabDetail(): HasMany
    {
        return $this->hasMany(RujukanLabDetail::class, 'rujukan_lab_id');
    }

    public function permintaan(): HasMany
    {
        return $this->hasMany(Permintaan::class, 'rujukan_lab_id');
    }
}
