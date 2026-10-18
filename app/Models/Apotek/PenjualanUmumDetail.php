<?php

namespace App\Models\Apotek;

use App\Models\Master\Obat;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenjualanUmumDetail extends ModelDasar
{
    protected $table = 'apt_penjualan_umum_detail';

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(PenjualanUmum::class, 'penjualan_id');
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
