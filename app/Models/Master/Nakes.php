<?php

namespace App\Models\Master;

use App\Models\ModelDasar;
use App\Models\Sys\Pengguna;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nakes extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_nakes';

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'aktif' => 'boolean',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }
}
