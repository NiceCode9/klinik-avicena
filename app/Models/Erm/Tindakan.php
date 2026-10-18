<?php

namespace App\Models\Erm;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tindakan extends ModelDasar
{
    protected $table = 'erm_tindakan';

    protected function casts(): array
    {
        return [
            'tarif' => 'decimal:2',
        ];
    }

    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(Pemeriksaan::class, 'pemeriksaan_id');
    }

    public function tindakan(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Master\Tindakan::class, 'tindakan_id');
    }
}
