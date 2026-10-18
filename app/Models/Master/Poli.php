<?php

namespace App\Models\Master;

use App\Models\Erm\Pemeriksaan;
use App\Models\ModelDasar;
use App\Models\Registrasi\Antrean;
use App\Models\Registrasi\PenghitungAntrean;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Poli extends ModelDasar
{
    use SoftDeletes;

    protected $table = 'mtr_poli';

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function dokter(): HasMany
    {
        return $this->hasMany(Dokter::class, 'poli_id');
    }

    public function tindakan(): HasMany
    {
        return $this->hasMany(Tindakan::class, 'poli_id');
    }

    public function penghitungAntrean(): HasMany
    {
        return $this->hasMany(PenghitungAntrean::class, 'poli_id');
    }

    public function antrean(): HasMany
    {
        return $this->hasMany(Antrean::class, 'poli_id');
    }

    public function pemeriksaan(): HasMany
    {
        return $this->hasMany(Pemeriksaan::class, 'poli_id');
    }
}
