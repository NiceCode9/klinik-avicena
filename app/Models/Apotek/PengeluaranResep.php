<?php

namespace App\Models\Apotek;

use App\Models\Erm\ResepItem;
use App\Models\Master\Obat;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengeluaranResep extends ModelDasar
{
    protected $table = 'apt_pengeluaran_resep';

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:3',
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

    public function stokBatch(): BelongsTo
    {
        return $this->belongsTo(StokBatch::class, 'stok_batch_id');
    }
}
