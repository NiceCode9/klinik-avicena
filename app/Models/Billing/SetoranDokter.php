<?php

namespace App\Models\Billing;

use App\Models\Master\Dokter;
use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SetoranDokter extends ModelDasar
{
    protected $table = 'bil_setoran_dokter';

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'jumlah_tunai' => 'decimal:2',
            'jumlah_transfer' => 'decimal:2',
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function dokter(): BelongsTo
    {
        return $this->belongsTo(Dokter::class, 'dokter_id');
    }

    public function diserahkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diserahkan_oleh');
    }
}
