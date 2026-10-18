<?php

namespace App\Models\Apotek;

use App\Models\Master\Obat;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturPasienDetail extends ModelDasar
{
    protected $table = 'apt_retur_pasien_detail';

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'nilai_kembali' => 'decimal:2',
        ];
    }

    public function retur(): BelongsTo
    {
        return $this->belongsTo(ReturPasien::class, 'retur_id');
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
