<?php

namespace App\Models\Apotek;

use App\Models\Master\Supplier;
use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturSupplier extends ModelDasar
{
    protected $table = 'apt_retur_supplier';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(Penerimaan::class, 'penerimaan_id');
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function returSupplierDetail(): HasMany
    {
        return $this->hasMany(ReturSupplierDetail::class, 'retur_id');
    }
}
