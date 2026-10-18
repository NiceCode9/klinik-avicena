<?php

namespace App\Models\Apotek;

use App\Models\Erm\Resep;
use App\Models\Master\Pasien;
use App\Models\ModelDasar;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReturPasien extends ModelDasar
{
    protected $table = 'apt_retur_pasien';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'total_pengembalian' => 'decimal:2',
        ];
    }

    public function pasien(): BelongsTo
    {
        return $this->belongsTo(Pasien::class, 'pasien_id');
    }

    public function resep(): BelongsTo
    {
        return $this->belongsTo(Resep::class, 'resep_id');
    }

    public function penjualan(): BelongsTo
    {
        return $this->belongsTo(PenjualanUmum::class, 'penjualan_id');
    }

    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function returPasienDetail(): HasMany
    {
        return $this->hasMany(ReturPasienDetail::class, 'retur_id');
    }
}
