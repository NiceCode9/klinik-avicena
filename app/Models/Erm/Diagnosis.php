<?php

namespace App\Models\Erm;

use App\Models\Master\Icd10;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Diagnosis extends ModelDasar
{
    protected $table = 'erm_diagnosis';

    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(Pemeriksaan::class, 'pemeriksaan_id');
    }

    public function icd10(): BelongsTo
    {
        return $this->belongsTo(Icd10::class, 'icd10_id');
    }
}
