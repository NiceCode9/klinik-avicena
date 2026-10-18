<?php

namespace App\Models\Erm;

use App\Models\Master\PemeriksaanLab;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RujukanLabDetail extends ModelDasar
{
    protected $table = 'erm_rujukan_lab_detail';

    public function rujukanLab(): BelongsTo
    {
        return $this->belongsTo(RujukanLab::class, 'rujukan_lab_id');
    }

    public function pemeriksaanLab(): BelongsTo
    {
        return $this->belongsTo(PemeriksaanLab::class, 'pemeriksaan_lab_id');
    }
}
