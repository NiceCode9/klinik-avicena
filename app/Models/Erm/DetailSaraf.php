<?php

namespace App\Models\Erm;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailSaraf extends ModelDasar
{
    protected $table = 'erm_detail_saraf';

    protected function casts(): array
    {
        return [
            'evaluasi_motorik' => 'array',
            'evaluasi_sensorik' => 'array',
            'saraf_kranial' => 'array',
        ];
    }

    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(Pemeriksaan::class, 'pemeriksaan_id');
    }
}
