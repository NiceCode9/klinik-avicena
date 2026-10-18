<?php

namespace App\Models\Apotek;

use App\Models\Master\Supplier;
use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penerimaan extends ModelDasar
{
    protected $table = 'apt_penerimaan';

    protected function casts(): array
    {
        return [
            'tanggal_faktur' => 'date',
            'tanggal_terima' => 'date',
            'total' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function penerima(): BelongsTo
    {
        return $this->belongsTo(User::class, 'penerima_id');
    }

    public function penerimaanDetail(): HasMany
    {
        return $this->hasMany(PenerimaanDetail::class, 'penerimaan_id');
    }

    public function returSupplier(): HasMany
    {
        return $this->hasMany(ReturSupplier::class, 'penerimaan_id');
    }
}
