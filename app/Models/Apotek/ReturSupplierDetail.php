<?php

namespace App\Models\Apotek;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturSupplierDetail extends ModelDasar
{
    protected $table = 'apt_retur_supplier_detail';

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
        ];
    }

    public function retur(): BelongsTo
    {
        return $this->belongsTo(ReturSupplier::class, 'retur_id');
    }

    public function stokBatch(): BelongsTo
    {
        return $this->belongsTo(StokBatch::class, 'stok_batch_id');
    }
}
