<?php

namespace App\Models\Erm;

use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPenyakitDalam extends ModelDasar
{
    protected $table = 'erm_detail_penyakit_dalam';

    protected function casts(): array
    {
        return [
            'review_sistem_organ' => 'array',
        ];
    }

    public function pemeriksaan(): BelongsTo
    {
        return $this->belongsTo(Pemeriksaan::class, 'pemeriksaan_id');
    }
}
