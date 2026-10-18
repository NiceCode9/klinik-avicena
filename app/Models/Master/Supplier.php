<?php

namespace App\Models\Master;

use App\Models\Apotek\Penerimaan;
use App\Models\Apotek\ReturSupplier;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_supplier';

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function penerimaan(): HasMany
    {
        return $this->hasMany(Penerimaan::class, 'supplier_id');
    }

    public function returSupplier(): HasMany
    {
        return $this->hasMany(ReturSupplier::class, 'supplier_id');
    }
}
