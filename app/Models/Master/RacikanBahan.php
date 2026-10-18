<?php

namespace App\Models\Master;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RacikanBahan extends ModelDasar
{
    protected $table = 'mtr_racikan_bahan';

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:3',
        ];
    }

    public function racikan(): BelongsTo
    {
        return $this->belongsTo(Racikan::class, 'racikan_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }
}
