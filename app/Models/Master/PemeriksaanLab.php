<?php

namespace App\Models\Master;

use App\Models\Erm\RujukanLabDetail;
use App\Models\Lab\PermintaanDetail;
use App\Models\ModelDasar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PemeriksaanLab extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_pemeriksaan_lab';

    protected function casts(): array
    {
        return [
            'tarif' => 'decimal:2',
            'aktif' => 'boolean',
        ];
    }

    public function parameterLab(): HasMany
    {
        return $this->hasMany(ParameterLab::class, 'pemeriksaan_lab_id');
    }

    public function rujukanLabDetail(): HasMany
    {
        return $this->hasMany(RujukanLabDetail::class, 'pemeriksaan_lab_id');
    }

    public function permintaanDetail(): HasMany
    {
        return $this->hasMany(PermintaanDetail::class, 'pemeriksaan_lab_id');
    }
}
