<?php

namespace App\Models\Apotek;

use App\Models\Master\Obat;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StokBatch extends ModelDasar
{
    protected $table = 'apt_stok_batch';

    protected function casts(): array
    {
        return [
            'tanggal_kedaluwarsa' => 'date',
            'harga_beli' => 'decimal:2',
            'jumlah_masuk' => 'decimal:2',
            'jumlah_sisa' => 'decimal:2',
        ];
    }

    public function obat(): BelongsTo
    {
        return $this->belongsTo(Obat::class, 'obat_id');
    }

    public function penerimaanDetail(): BelongsTo
    {
        return $this->belongsTo(PenerimaanDetail::class, 'penerimaan_detail_id');
    }

    public function mutasiStok(): HasMany
    {
        return $this->hasMany(MutasiStok::class, 'stok_batch_id');
    }

    public function pengeluaranResep(): HasMany
    {
        return $this->hasMany(PengeluaranResep::class, 'stok_batch_id');
    }

    public function penjualanUmumDetail(): HasMany
    {
        return $this->hasMany(PenjualanUmumDetail::class, 'stok_batch_id');
    }

    public function returSupplierDetail(): HasMany
    {
        return $this->hasMany(ReturSupplierDetail::class, 'stok_batch_id');
    }

    public function returPasienDetail(): HasMany
    {
        return $this->hasMany(ReturPasienDetail::class, 'stok_batch_id');
    }

    public function opnameDetail(): HasMany
    {
        return $this->hasMany(OpnameDetail::class, 'stok_batch_id');
    }
}
