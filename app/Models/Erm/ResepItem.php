<?php

namespace App\Models\Erm;

use App\Models\Apotek\PengeluaranResep;
use App\Models\Master\Obat;
use App\Models\Master\Racikan;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResepItem extends ModelDasar
{
    protected $table = 'erm_resep_item';

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'harga_satuan' => 'decimal:2',
            'biaya_jasa_racik' => 'decimal:2',
        ];
    }

    public function resep(): BelongsTo
    {
        return $this->belongsTo(Resep::class, 'resep_id');
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function racikan(): BelongsTo
    {
        return $this->belongsTo(Racikan::class, 'racikan_id');
    }

    public function resepRacikanBahan(): HasMany
    {
        return $this->hasMany(ResepRacikanBahan::class, 'resep_item_id');
    }

    public function pengeluaranResep(): HasMany
    {
        return $this->hasMany(PengeluaranResep::class, 'resep_item_id');
    }
}
