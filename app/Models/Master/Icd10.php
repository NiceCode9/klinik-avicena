<?php

namespace App\Models\Master;

use App\Models\Erm\Diagnosis;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Icd10 extends ModelDasar
{
    protected $table = 'mtr_icd10';

    public function diagnosis(): HasMany
    {
        return $this->hasMany(Diagnosis::class, 'icd10_id');
    }
}
