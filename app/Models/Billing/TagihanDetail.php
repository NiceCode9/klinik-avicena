<?php

namespace App\Models\Billing;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagihanDetail extends ModelDasar
{
    protected $table = 'bil_tagihan_detail';

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class, 'tagihan_id');
    }
}
