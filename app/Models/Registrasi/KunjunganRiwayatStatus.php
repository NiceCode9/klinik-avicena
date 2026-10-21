<?php

namespace App\Models\Registrasi;

use App\Models\ModelDasar;
use App\Models\Sys\Pengguna;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KunjunganRiwayatStatus extends ModelDasar
{
    protected $table = 'reg_kunjungan_riwayat_status';

    protected function casts(): array
    {
        return ['waktu' => 'datetime'];
    }

    public function kunjungan(): BelongsTo
    {
        return $this->belongsTo(Kunjungan::class, 'kunjungan_id');
    }

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh');
    }
}
