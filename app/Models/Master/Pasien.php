<?php

namespace App\Models\Master;

use App\Models\Apotek\PenjualanUmum;
use App\Models\Apotek\ReturPasien;
use App\Models\ModelDasar;
use App\Models\Registrasi\Kunjungan;
use App\Services\NikHasher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pasien extends ModelDasar
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'mtr_pasien';

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'tanggal_lahir' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Pasien $pasien) {
            $pasien->nik_hash = NikHasher::hash($pasien->nik);
        });
    }

    public function kunjungan(): HasMany
    {
        return $this->hasMany(Kunjungan::class, 'pasien_id');
    }

    public function penjualanUmum(): HasMany
    {
        return $this->hasMany(PenjualanUmum::class, 'pasien_id');
    }

    public function returPasien(): HasMany
    {
        return $this->hasMany(ReturPasien::class, 'pasien_id');
    }
}
