<?php

namespace App\Models\Apotek;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpnameDetail extends ModelDasar
{
    protected $table = 'apt_opname_detail';

    protected function casts(): array
    {
        return [
            'stok_sistem' => 'decimal:2',
            'stok_fisik' => 'decimal:2',
            'selisih' => 'decimal:2',
        ];
    }

    public function opname(): BelongsTo
    {
        return $this->belongsTo(Opname::class, 'opname_id');
    }

    public function stokBatch(): BelongsTo
    {
        return $this->belongsTo(StokBatch::class, 'stok_batch_id');
    }
}
