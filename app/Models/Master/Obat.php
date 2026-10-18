<?php

namespace App\Models\Master;

use App\Models\Apotek\MutasiStok;
use App\Models\Apotek\PenerimaanDetail;
use App\Models\Apotek\PengeluaranResep;
use App\Models\Apotek\PenjualanUmumDetail;
use App\Models\Apotek\ReturPasienDetail;
use App\Models\Apotek\StokBatch;
use App\Models\Erm\ResepItem;
use App\Models\Erm\ResepRacikanBahan;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Obat extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_obat';

    protected function casts(): array
    {
        return [
            'stok_minimum' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function kategoriObat(): BelongsTo
    {
        return $this->belongsTo(KategoriObat::class, 'kategori_obat_id');
    }

    public function satuan(): BelongsTo
    {
        return $this->belongsTo(Satuan::class, 'satuan_id');
    }

    public function racikanBahan(): HasMany
    {
        return $this->hasMany(RacikanBahan::class, 'obat_id');
    }

    public function resepItem(): HasMany
    {
        return $this->hasMany(ResepItem::class, 'obat_id');
    }

    public function resepRacikanBahan(): HasMany
    {
        return $this->hasMany(ResepRacikanBahan::class, 'obat_id');
    }

    public function penerimaanDetail(): HasMany
    {
        return $this->hasMany(PenerimaanDetail::class, 'obat_id');
    }

    public function stokBatch(): HasMany
    {
        return $this->hasMany(StokBatch::class, 'obat_id');
    }

    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class, 'obat_id');
    }

    public function pengeluaranResep(): HasMany
    {
        return $this->hasMany(PengeluaranResep::class, 'obat_id');
    }

    public function penjualanUmumDetail(): HasMany
    {
        return $this->hasMany(PenjualanUmumDetail::class, 'obat_id');
    }

    public function returPasienDetail(): HasMany
    {
        return $this->hasMany(ReturPasienDetail::class, 'obat_id');
    }
}
