<?php

namespace App\Models\Apotek;

use App\Models\Master\Obat;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PenerimaanDetail extends ModelDasar
{
    protected $table = 'apt_penerimaan_detail';

    protected function casts(): array
    {
        return [
            'tanggal_kedaluwarsa' => 'date',
            'jumlah' => 'decimal:2',
            'harga_beli' => 'decimal:2',
        ];
    }

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(Penerimaan::class, 'penerimaan_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function stokBatch(): HasMany
    {
        return $this->hasMany(StokBatch::class, 'penerimaan_detail_id');
    }
}
