<?php

namespace App\Models\Apotek;

use App\Models\Master\Obat;
use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MutasiStok extends ModelDasar
{
    protected $table = 'apt_mutasi_stok';

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'saldo_setelah' => 'decimal:2',
        ];
    }

    public function stokBatch(): BelongsTo
    {
        return $this->belongsTo(StokBatch::class, 'stok_batch_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
