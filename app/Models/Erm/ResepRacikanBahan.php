<?php

namespace App\Models\Erm;

use App\Models\Master\Obat;
use App\Models\Master\Satuan;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResepRacikanBahan extends ModelDasar
{
    protected $table = 'erm_resep_racikan_bahan';

    protected function casts(): array
    {
        return [
            'jumlah_bahan' => 'decimal:3',
        ];
    }

    public function resepItem(): BelongsTo
    {
        return $this->belongsTo(ResepItem::class, 'resep_item_id');
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
