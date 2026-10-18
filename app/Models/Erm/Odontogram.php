<?php

namespace App\Models\Erm;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Odontogram extends ModelDasar
{
    protected $table = 'erm_odontogram';

    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(Pemeriksaan::class, 'pemeriksaan_id');
    }
}
